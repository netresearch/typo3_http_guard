#!/usr/bin/env bash
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
set -euo pipefail
umask 077
package_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)
cd "$package_dir"
native_mode=${HTTP_GUARD_NATIVE_MODE:-integration}
case "$native_mode" in
    integration|mutation) ;;
    *) printf 'Unsupported native mode: %s\n' "$native_mode" >&2; exit 2 ;;
esac
for tool in docker flock openssl php curl python3; do
    command -v "$tool" >/dev/null || { printf 'Required tool unavailable: %s\n' "$tool" >&2; exit 2; }
done
# One lock per local user, held through preparation, execution and teardown.
# Different users/remote daemons are still fenced by Docker's fixed-subnet IPAM.
lock_parent=${TMPDIR:-/tmp}
if [[ -n ${XDG_RUNTIME_DIR:-} && -d $XDG_RUNTIME_DIR && -O $XDG_RUNTIME_DIR && -w $XDG_RUNTIME_DIR && ! -L $XDG_RUNTIME_DIR ]]; then
    lock_parent=$XDG_RUNTIME_DIR
fi
lock_dir="$lock_parent/http-guard-locks-$UID"
mkdir -p "$lock_dir"
[[ ! -L "$lock_dir" && -O "$lock_dir" ]] || { printf 'Unsafe native lock directory.\n' >&2; exit 2; }
chmod 700 "$lock_dir"
exec 9>"$lock_dir/native.lock"
flock -n 9 || { printf 'Another native run owns the controlled target lock.\n' >&2; exit 2; }
mkdir -p "$package_dir/.Build/runtime"
run_dir=$(mktemp -d "$package_dir/.Build/runtime/native.XXXXXXXX")
run_id="http-guard-native-${run_dir##*/}"
owned_containers=()
owned_networks=()
cleanup() {
    local id
    for id in "${owned_containers[@]}"; do docker rm -f "$id" >/dev/null 2>&1 || true; done
    for id in "${owned_networks[@]}"; do docker network rm "$id" >/dev/null 2>&1 || true; done
    # Retain logs and version records for failed and successful runs; remove
    # only freshly generated private keys owned by this run.
    rm -rf "$run_dir/certificates"
}
trap cleanup EXIT
trap 'exit 130' INT
trap 'exit 143' TERM
# Reserve all fixed host-loopback ports without sending to existing listeners.
python3 - <<'PY'
import socket
for port in (18090,18091,18443,18444):
    with socket.socket() as s:
        s.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1)
        s.bind(('127.0.0.1',port))
with socket.socket(socket.AF_INET6) as s:
    s.setsockopt(socket.SOL_SOCKET,socket.SO_REUSEADDR,1)
    s.setsockopt(socket.IPPROTO_IPV6,socket.IPV6_V6ONLY,1)
    s.bind(('::1',18090))
PY
cert_dir="$run_dir/certificates"
mkdir "$cert_dir"
openssl req -x509 -newkey rsa:2048 -nodes -days 2 -keyout "$cert_dir/ca.key" -out "$cert_dir/ca.crt" -subj '/CN=HTTP Guard SYNTHETIC TEST CA' > "$run_dir/certificate.log" 2>&1
openssl req -newkey rsa:2048 -nodes -keyout "$cert_dir/server.key" -out "$cert_dir/server.csr" -subj '/CN=guard.test' >> "$run_dir/certificate.log" 2>&1
printf 'subjectAltName=DNS:guard.test,DNS:other.test,DNS:erp.test\nextendedKeyUsage=serverAuth\n' > "$cert_dir/server.ext"
openssl x509 -req -days 2 -in "$cert_dir/server.csr" -CA "$cert_dir/ca.crt" -CAkey "$cert_dir/ca.key" -CAcreateserial -out "$cert_dir/server.crt" -extfile "$cert_dir/server.ext" >> "$run_dir/certificate.log" 2>&1
openssl req -newkey rsa:2048 -nodes -keyout "$cert_dir/client.key" -out "$cert_dir/client.csr" -subj '/CN=HTTP Guard SYNTHETIC TEST CLIENT' >> "$run_dir/certificate.log" 2>&1
printf 'extendedKeyUsage=clientAuth\n' > "$cert_dir/client.ext"
openssl x509 -req -days 2 -in "$cert_dir/client.csr" -CA "$cert_dir/ca.crt" -CAkey "$cert_dir/ca.key" -CAcreateserial -out "$cert_dir/client.crt" -extfile "$cert_dir/client.ext" >> "$run_dir/certificate.log" 2>&1
chmod 755 "$cert_dir"
chmod 644 "$cert_dir/server.key" "$cert_dir/server.crt" "$cert_dir/ca.crt"
label="org.netresearch.http-guard.run=$run_id"
# Capture immutable IDs at creation; teardown never enumerates/deletes foreign
# resources, including similarly named containers from another checkout.
for spec in 'public 203.0.115.0/24' 'private 10.23.4.0/24'; do
    read -r kind subnet <<< "$spec"
    id=$(docker network create --internal --label "$label" --subnet "$subnet" "$run_id-$kind")
    owned_networks+=("$id")
done
id=$(docker network create --internal --label "$label" --ipv6 --subnet 203.0.116.0/24 --subnet 2600:7e00:6775:6172::/64 "$run_id-v6")
owned_networks+=("$id")
image=python@sha256:f85c5697265c178cc6887276c55fe16cf3d14ca35c3df6a5eab3b360534a55d2
for spec in 'public-a 203.0.115.100 public' 'public-b 203.0.115.101 public' 'private 10.23.4.12 private'; do
    read -r target address network <<< "$spec"
    id=$(docker create --name "$run_id-$target" --label "$label" --network "$run_id-$network" --ip "$address" \
        --read-only --cap-drop ALL --security-opt no-new-privileges:true --user 65534:65534 \
        --mount "type=bind,src=$package_dir/Tests/HttpGuard/Integration/wire_server.py,dst=/wire_server.py,readonly" \
        --mount "type=bind,src=$cert_dir,dst=/certificates,readonly" "$image" python /wire_server.py --label "$target" --cert-dir /certificates)
    owned_containers+=("$id")
    if [[ $target == public-a || $target == public-b ]]; then
        suffix=100
        [[ $target == public-a ]] || suffix=101
        docker network connect --ip6 "2600:7e00:6775:6172::$suffix" "$run_id-v6" "$id"
    fi
    docker start "$id" >/dev/null
done
id=$(docker create --name "$run_id-loopback" --label "$label" --network host \
    --read-only --cap-drop ALL --security-opt no-new-privileges:true --user 65534:65534 \
    --mount "type=bind,src=$package_dir/Tests/HttpGuard/Integration/wire_server.py,dst=/wire_server.py,readonly" \
    --mount "type=bind,src=$cert_dir,dst=/certificates,readonly" "$image" python /wire_server.py --label loopback --cert-dir /certificates --bind 127.0.0.1 --bind6 ::1 --port-offset 10000)
owned_containers+=("$id")
docker start "$id" >/dev/null
for address in 203.0.115.100:8091 203.0.115.101:8091 10.23.4.12:8091 127.0.0.1:18091; do
    ready=false
    for unused in {1..30}; do
        if curl --noproxy '*' --silent --fail --max-time 1 "http://$address/reset" >/dev/null; then ready=true; break; fi
        printf 'Waiting for owned target %s (%s/30).\n' "$address" "$unused"
        sleep 1
    done
    "$ready" || { printf 'Owned target did not become ready: %s\n' "$address" >&2; exit 2; }
done
autoload=$(realpath "${HTTP_GUARD_TEST_AUTOLOAD:-$package_dir/.Build/vendor/autoload.php}")
phpunit=$(realpath "${HTTP_GUARD_PHPUNIT:-$package_dir/.Build/vendor/bin/phpunit}")
php_version=${HTTP_GUARD_EXPECTED_PHP:-$(php -r 'echo PHP_MAJOR_VERSION, ".", PHP_MINOR_VERSION;')}
# shellcheck source=/dev/null
source "$package_dir/Build/Scripts/runTests.conf"
php_image=${HTTP_GUARD_NATIVE_PHP_IMAGE:-$(php_image "$php_version")}
[[ $php_image =~ @sha256:[0-9a-f]{64}$ ]] || { printf 'Native PHP image must be digest pinned.\n' >&2; exit 2; }
mounts=(--mount "type=bind,src=$package_dir,dst=$package_dir"
    --mount "type=bind,src=$cert_dir,dst=$package_dir/Tests/HttpGuard/Integration/certificates,readonly")
for file in "$autoload" "$phpunit"; do
    vendor=$(dirname "$(dirname "$file")")
    [[ $file != */bin/phpunit ]] || vendor=$(dirname "$(dirname "$file")")
    [[ $file != */autoload.php ]] || vendor=$(dirname "$file")
    if [[ $vendor != "$package_dir"/* && $vendor != "${mounted_vendor:-}" ]]; then
        mounts+=(--mount "type=bind,src=$vendor,dst=$vendor,readonly")
        mounted_vendor=$vendor
    fi
done
printf '%s\n' "$run_id" > "$run_dir/run-id.txt"
docker image inspect "$php_image" > "$run_dir/php-image.json"
id=$(docker create --name "$run_id-probe" --label "$label" --network host \
    "${mounts[@]}" --workdir "$package_dir" --user "$(id -u):$(id -g)" \
    --cap-drop ALL --security-opt no-new-privileges:true "$php_image" \
    php "$package_dir/Build/Scripts/assert-test-runtime.php" "$autoload" "$php_version" "${HTTP_GUARD_EXPECTED_CORE:-}")
owned_containers+=("$id")
docker start --attach "$id" | tee "$run_dir/runtime.json"
case "$native_mode" in
    integration)
        coverage=off
        test_command=(php "$phpunit" --configuration "$package_dir/phpunit.xml"
            --testsuite HttpGuardIntegration --log-junit "$run_dir/junit.xml" "$@") ;;
    mutation)
        coverage=coverage
        test_command=(php "$package_dir/.Build/vendor/bin/infection"
            --configuration "$package_dir/infection.native.json5" --threads=1
            --with-uncovered --with-timeouts --only-covering-test-cases
            --no-progress --show-mutations=0 "$@") ;;
esac
id=$(docker create --name "$run_id-tests" --label "$label" --network host \
    "${mounts[@]}" --workdir "$package_dir" --user "$(id -u):$(id -g)" \
    --cap-drop ALL --security-opt no-new-privileges:true --add-host nss-only-guard.test:10.23.4.12 \
    --env "HTTP_GUARD_TEST_AUTOLOAD=$autoload" --env "XDEBUG_MODE=$coverage" \
    "$php_image" "${test_command[@]}")
owned_containers+=("$id")
docker inspect "$id" > "$run_dir/test-container.json"
printf 'Native %s run %s; retained evidence: %s\n' "$native_mode" "$run_id" "$run_dir"
docker start --attach "$id" | tee "$run_dir/native.log"
