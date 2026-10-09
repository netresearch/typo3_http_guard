#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: Netresearch DTT GmbH
set -euo pipefail

# Wrap the required Core-style runner; the tested PHP remains inside its job.
# The two owned NICs are synthetic, isolated Docker addresses, not internet hosts.
vault_guard_root="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
vault_guard_public_network="${VAULT_GUARD_PUBLIC_NETWORK:-http-guard-production-public}"
vault_guard_private_network="${VAULT_GUARD_PRIVATE_NETWORK:-http-guard-production-private}"
vault_guard_php="${VAULT_GUARD_PHP:-8.5}"
cd "$vault_guard_root"
test -d .Build/nr-http-guard-extension/Classes/HttpGuard
# Install shared zero-contact counters without resetting an existing target.
bash .Build/nr-http-guard-extension/Tests/HttpGuard/Integration/prepare-wire.sh
for vault_guard_pair in "$vault_guard_public_network:203.0.115.0/24" "$vault_guard_private_network:10.23.4.0/24"; do
    vault_guard_network="${vault_guard_pair%%:*}"
    vault_guard_subnet="${vault_guard_pair#*:}"
    if ! docker network inspect "$vault_guard_network" >/dev/null 2>&1; then
        docker network create --internal --subnet "$vault_guard_subnet" "$vault_guard_network" >/dev/null
    fi
done
printf '%s\n' '{"publicIp":"203.0.115.150","privateIp":"10.23.4.150"}' > .Build/http-guard-network.json
vault_guard_existing="$(docker ps -aq --filter 'name=^functional-')"
Build/Scripts/runTests.sh -s functional -p "$vault_guard_php" -d sqlite "$@" &
vault_guard_runner=$!
vault_guard_stop_runner() {
    if kill -0 "$vault_guard_runner" 2>/dev/null; then
        kill "$vault_guard_runner" 2>/dev/null || true
        wait "$vault_guard_runner" 2>/dev/null || true
    fi
}
trap vault_guard_stop_runner EXIT
vault_guard_attached=false
while kill -0 "$vault_guard_runner" 2>/dev/null; do
    while IFS= read -r vault_guard_container; do
        test -n "$vault_guard_container" || continue
        if printf '%s\n' "$vault_guard_existing" | rg -q "^${vault_guard_container}$"; then
            continue
        fi
        vault_guard_mounts="$(docker inspect --format '{{range .Mounts}}{{.Source}}{{println}}{{end}}' "$vault_guard_container" 2>/dev/null || true)"
        if ! printf '%s\n' "$vault_guard_mounts" | rg -Fqx "$vault_guard_root"; then
            continue
        fi
        docker network connect --ip 203.0.115.150 "$vault_guard_public_network" "$vault_guard_container"
        docker network connect --ip 10.23.4.150 "$vault_guard_private_network" "$vault_guard_container"
        vault_guard_attached=true
        break
    done < <(docker ps -q --filter 'name=^functional-')
    if "$vault_guard_attached"; then
        break
    fi
    sleep 0.1
done
set +e
wait "$vault_guard_runner"
vault_guard_result=$?
set -e
if ! "$vault_guard_attached"; then
    printf '%s\n' 'No matching owned functional job was attached to both fixture networks.' >&2
    exit 1
fi
exit "$vault_guard_result"
