#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: Netresearch DTT GmbH
set -euo pipefail
project_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$project_root"
report_root="${HTTP_GUARD_FUZZ_REPORT_DIR:-$project_root/.Build/reports/fuzz}"
mkdir -p "$report_root"
run_root="$(mktemp -d "$report_root/run-XXXXXX")"
tool_path="$project_root/.Build/vendor/bin/php-fuzzer"
if [[ ! -x "$tool_path" ]]; then
    echo 'Install development dependencies before running offline fuzzing.' >&2
    exit 1
fi
seed_values="$(php Build/Scripts/fuzz-evidence.php seeds Tests/Fuzz/seeds.json)"
mapfile -t seeds <<< "$seed_values"
shopt -s nullglob
for seed in "${seeds[@]}"; do
    seed_root="$run_root/seed-$seed"
    mkdir -p "$seed_root/corpus"
    cp "$project_root"/Tests/Fuzz/corpus/* "$seed_root/corpus/"
    corpus_inputs=("$seed_root"/corpus/*)
    expected_calls=$((10000 + ${#corpus_inputs[@]}))
    (
        cd "$seed_root"
        HTTP_GUARD_FUZZ_SEED="$seed" "$tool_path" fuzz "$project_root/Tests/Fuzz/fuzz-target.php" corpus --max-runs=10000 > fuzz.log 2>&1
    )
    # Upstream 0.0.11 also returns zero for initial-corpus crashes.
    calls="$(php Build/Scripts/fuzz-evidence.php verify "$seed_root" "$expected_calls")"
    printf 'Offline fuzz seed %s: %s generated cases, no crashes.\n' "$seed" "$((calls - ${#corpus_inputs[@]}))"
done
printf 'Offline fuzz evidence: %s\n' "$run_root"
