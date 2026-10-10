#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
set -euo pipefail
package_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
cd -- "$package_dir"
find Classes Configuration Tests Build -type f -name '*.php' \
    -not -path 'Build/Reports/*' -not -path '*/certificates/*' -print0 \
    | xargs -0 -n1 php -l
for source in ext_localconf.php ext_emconf.php .php-cs-fixer.dist.php rector.php; do
    php -l "$source"
done
