#!/usr/bin/env bash
set -euo pipefail
suite=unit
php_version="${PHP_VERSION:-8.5}"
while getopts 's:p:' option; do
    case "$option" in
        s) suite=$OPTARG ;;
        p) php_version=$OPTARG ;;
        *) exit 2 ;;
    esac
done
package_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
actual_php=$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')
if [[ $actual_php != "$php_version" ]]; then
    printf 'Requested PHP %s; available PHP is %s.\n' "$php_version" "$actual_php" >&2
    exit 2
fi
case "$suite" in
    unit) php "${HTTP_GUARD_PHPUNIT:-$package_dir/vendor/bin/phpunit}" -c "$package_dir/phpunit.xml" ;;
    integration) php "$package_dir/Tests/Integration/production-bootstrap.php" "${HTTP_GUARD_FIXTURE:?Set HTTP_GUARD_FIXTURE to a prepared real TYPO3 fixture}" ;;
    *) printf 'Unsupported suite: %s (requested PHP %s)\n' "$suite" "$php_version" >&2; exit 2 ;;
esac
