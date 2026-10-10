#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
set -euo pipefail
package_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
cd -- "$package_dir"
mode=${1:?Select check, fix or files}
shift
case "$mode" in
    check) arguments=(--dry-run --diff "$@") ;;
    fix) arguments=("$@") ;;
    files)
        [[ $# -gt 0 ]] || { printf 'Provide guarded AST file paths.\n' >&2; exit 2; }
        arguments=(--path-mode=intersection "$@") ;;
    *) printf 'Unsupported style mode: %s\n' "$mode" >&2; exit 2 ;;
esac
for scope in extension kernel; do
    HTTP_GUARD_CGL_SCOPE=$scope php .Build/vendor/bin/php-cs-fixer fix \
        --config=.php-cs-fixer.dist.php "${arguments[@]}"
done
