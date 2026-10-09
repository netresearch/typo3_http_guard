#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
set -euo pipefail
package_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$package_dir"
suite=unit
php_version=
core_version=
container_bin=
while getopts 's:p:t:b:h' option; do
    case "$option" in
        s) suite=$OPTARG ;;
        p) php_version=$OPTARG ;;
        t) core_version=$OPTARG ;;
        b) container_bin=$OPTARG ;;
        h)
            printf '%s\n' 'HTTP Guard tests: -s unit|native|mutation|mutation-native|architecture|fuzz|performance' \
                'No -p/-t: use installed host PHP and the project suite.' \
                'Explicit -p/-t: delegate to the installed shared container runner.' \
                '-t updates the selected Core in composer.json: use an isolated checkout.' \
                'Use -- before PHPUnit/tool arguments. Native targets are owned per run.'
            exit 0 ;;
        *) exit 2 ;;
    esac
done
shift $((OPTIND - 1))
# Preserve the safe Core floors when accepting the shared runner's shorthand.
case "$core_version" in
    13|13.4) core_version='^13.4.36' ;;
    14|14.3) core_version='^14.3.8' ;;
esac
if [[ -n "$php_version" || -n "$core_version" || -n "$container_bin" ]]; then
    runner="$package_dir/.Build/vendor/netresearch/typo3-ci-workflows/assets/Build/Scripts/runTests.sh"
    [[ -x "$runner" ]] || { printf 'Install the shared CI development package first: composer install.\n' >&2; exit 2; }
    [[ -n "$php_version" ]] || php_version=$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')
    runtime_args=(-p "$php_version")
    [[ -z "$container_bin" ]] || runtime_args+=(-b "$container_bin")
    if [[ -n "$core_version" ]]; then
        [[ ! -L "$package_dir/.Build/vendor" ]] || { printf 'Core selection requires a checkout-owned vendor directory, not a shared symlink.\n' >&2; exit 2; }
        # The upstream -t partial update needs a lock absent from fresh extension
        # checkouts. Delegate both operations to its Composer suite instead.
        bash "$runner" -s composer "${runtime_args[@]}" -- require \
            --no-interaction --no-progress --no-scripts --no-update "typo3/cms-core:$core_version"
        # A Core-major switch also needs reverse-dependent development adapters
        # to resolve again, rather than keeping a Core13-only adapter locked.
        update_args=(--no-interaction --no-progress --no-scripts --no-autoloader --with-all-dependencies)
        bash "$runner" -s composer "${runtime_args[@]}" -- update "${update_args[@]}"
        # Restart Composer before dumping: a Core-major change can replace its
        # class-alias plugin while the old plugin classes are still loaded.
        bash "$runner" -s composer "${runtime_args[@]}" -- dump-autoload --no-scripts
        export HTTP_GUARD_EXPECTED_CORE="$core_version"
    fi
    bash "$runner" -s composer "${runtime_args[@]}" -- exec -- \
        php Build/Scripts/assert-test-runtime.php .Build/vendor/autoload.php "$php_version" "$core_version"
    case "$suite" in
        architecture|mutation|fuzz|performance|integration)
            # These project scopes differ from generic shared suites. Execute
            # the host entry point in the selected shared container verbatim.
            exec bash "$runner" -s composer "${runtime_args[@]}" -- exec -- \
                bash Build/Scripts/runTests.sh -s "$suite" -- "$@" ;;
    esac
    if [[ $suite == mutation-native ]]; then
        export HTTP_GUARD_NATIVE_MODE=mutation
        suite=native
    fi
    args=(-s "$suite" "${runtime_args[@]}")
    # The installed shared runner owns generic containers and their cleanup.
    exec bash "$runner" "${args[@]}" -- "$@"
fi
case "$suite" in
    unit)
        exec php "${HTTP_GUARD_PHPUNIT:-$package_dir/.Build/vendor/bin/phpunit}" \
            --configuration "$package_dir/phpunit.xml" --testsuite Unit "$@" ;;
    native)
        exec bash "$package_dir/Build/Scripts/run-native-tests.sh" "$@" ;;
    mutation-native)
        HTTP_GUARD_NATIVE_MODE=mutation exec bash "$package_dir/Build/Scripts/run-native-tests.sh" "$@" ;;
    integration)
        exec php "$package_dir/Tests/Integration/production-bootstrap.php" \
            "${HTTP_GUARD_FIXTURE:?Set HTTP_GUARD_FIXTURE to a prepared genuine Core fixture}" "$@" ;;
    architecture)
        exec php "$package_dir/.Build/vendor/bin/phpstan" analyse \
            --configuration "$package_dir/Build/phpstan-architecture.neon" \
            --autoload-file="$package_dir/Tests/Architecture/bootstrap.php" --no-progress "$@" ;;
    mutation)
        export XDEBUG_MODE=coverage
        export HTTP_GUARD_TEST_AUTOLOAD="${HTTP_GUARD_TEST_AUTOLOAD:-$package_dir/.Build/vendor/autoload.php}"
        exec php "$package_dir/.Build/vendor/bin/infection" \
            --configuration="$package_dir/infection.json5" --with-uncovered --with-timeouts \
            --only-covering-test-cases --threads=4 --no-progress --show-mutations=0 "$@" ;;
    fuzz)
        exec bash "$package_dir/Build/Scripts/run-offline-fuzz.sh" "$@" ;;
    performance)
        export XDEBUG_MODE=off
        exec php "$package_dir/Build/Scripts/benchmark-policy.php" "$@" ;;
    *) printf 'Unsupported suite: %s. Use -h for project suites.\n' "$suite" >&2; exit 2 ;;
esac
