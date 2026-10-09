#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
set -euo pipefail
package_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
cd -- "$package_dir"
autoload="${HTTP_GUARD_TEST_AUTOLOAD:-$package_dir/.Build/vendor/autoload.php}"
test -f "$autoload" || { printf 'Install the development dependencies first.\n' >&2; exit 2; }
export HTTP_GUARD_TEST_AUTOLOAD="$autoload"
core=$(php -r 'require $argv[1]; echo (new TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();' "$autoload")
case "$core" in
    13) profile=Build/phpstan-typo3-core13.neon; bootstrap=Build/phpstan-bootstrap13.php ;;
    14) profile=Build/phpstan-typo3.neon; bootstrap=Build/phpstan-bootstrap14.php ;;
    *) printf 'Unsupported installed TYPO3 major: %s\n' "$core" >&2; exit 2 ;;
esac
printf 'Analyse the embedded kernel and the actual TYPO3 %s adapter at level 10.\n' "$core"
php .Build/vendor/bin/phpstan analyse --configuration Build/phpstan-http-guard.neon --no-progress "$@"
php .Build/vendor/bin/phpstan analyse --configuration "$profile" --autoload-file "$bootstrap" --no-progress "$@"
