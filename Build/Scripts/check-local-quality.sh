#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
set -euo pipefail
package_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
cd -- "$package_dir"
for tool in composer php python3; do
    command -v "$tool" >/dev/null || { printf 'Required local tool missing: %s\n' "$tool" >&2; exit 2; }
done
for tool in php-cs-fixer phpstan; do
    test -x ".Build/vendor/bin/$tool" || { printf 'Install development dependencies; missing %s.\n' "$tool" >&2; exit 2; }
done
python3 Build/Scripts/verify-harness.py
composer validate --strict --no-check-publish
composer ci:test:php:cgl
composer ci:test:php:phpstan
python3 Build/Scripts/check-composer-qualification.py
python3 Build/Scripts/check-synthetic-fixtures.py
python3 Build/Scripts/check-research-headers.py
