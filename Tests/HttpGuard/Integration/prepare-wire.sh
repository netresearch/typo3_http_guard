#!/usr/bin/env bash
set -euo pipefail
fixture_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
cert_dir=${HTTP_GUARD_CERT_DIR:-"$fixture_dir/certificates"}
mkdir -p "$cert_dir"
if [[ ! -f "$cert_dir/server.crt" ]]; then
    openssl req -x509 -newkey rsa:2048 -nodes -days 3650 -keyout "$cert_dir/ca.key" -out "$cert_dir/ca.crt" -subj '/CN=HTTP Guard SYNTHETIC TEST CA' >/dev/null 2>&1
    openssl req -newkey rsa:2048 -nodes -keyout "$cert_dir/server.key" -out "$cert_dir/server.csr" -subj '/CN=guard.test' >/dev/null 2>&1
    printf 'subjectAltName=DNS:guard.test,DNS:other.test,DNS:erp.test\nextendedKeyUsage=serverAuth\n' > "$cert_dir/server.ext"
    openssl x509 -req -days 3650 -in "$cert_dir/server.csr" -CA "$cert_dir/ca.crt" -CAkey "$cert_dir/ca.key" -CAcreateserial -out "$cert_dir/server.crt" -extfile "$cert_dir/server.ext" >/dev/null 2>&1
    openssl req -newkey rsa:2048 -nodes -keyout "$cert_dir/client.key" -out "$cert_dir/client.csr" -subj '/CN=HTTP Guard SYNTHETIC TEST CLIENT' >/dev/null 2>&1
    printf 'extendedKeyUsage=clientAuth\n' > "$cert_dir/client.ext"
    openssl x509 -req -days 3650 -in "$cert_dir/client.csr" -CA "$cert_dir/ca.crt" -CAkey "$cert_dir/ca.key" -CAcreateserial -out "$cert_dir/client.crt" -extfile "$cert_dir/client.ext" >/dev/null 2>&1
fi
if ! docker network inspect http-guard-production-public >/dev/null 2>&1; then
    docker network create --internal --subnet 203.0.115.0/24 http-guard-production-public >/dev/null
fi
if ! docker network inspect http-guard-production-private >/dev/null 2>&1; then
    docker network create --internal --subnet 10.23.4.0/24 http-guard-production-private >/dev/null
fi
if ! docker network inspect http-guard-production-v6 >/dev/null 2>&1; then
    docker network create --internal --ipv6 --subnet 203.0.116.0/24 --subnet 2600:7e00:6775:6172::/64 http-guard-production-v6 >/dev/null
fi
for spec in 'public-a 203.0.115.100 public' 'public-b 203.0.115.101 public' 'private 10.23.4.12 private'; do
    read -r label address network_kind <<< "$spec"
    name="http-guard-production-$label"
    if ! docker container inspect "$name" >/dev/null 2>&1; then
        docker run -d --name "$name" --network "http-guard-production-$network_kind" --ip "$address" \
            --mount "type=bind,src=$fixture_dir/wire_server.py,dst=/wire_server.py,readonly" \
            --mount "type=bind,src=$cert_dir,dst=/certificates,readonly" \
            python:3.14-slim python /wire_server.py --label "$label" --cert-dir /certificates >/dev/null
    fi
done
if ! docker container inspect http-guard-production-loopback >/dev/null 2>&1; then
    docker run -d --name http-guard-production-loopback --network host \
        --mount "type=bind,src=$fixture_dir/wire_server.py,dst=/wire_server.py,readonly" \
        --mount "type=bind,src=$cert_dir,dst=/certificates,readonly" \
        python:3.14-slim python /wire_server.py --label loopback --cert-dir /certificates \
        --bind 127.0.0.1 --bind6 ::1 --port-offset 10000 >/dev/null
fi
for spec in 'public-a 2600:7e00:6775:6172::100' 'public-b 2600:7e00:6775:6172::101'; do
    read -r label address <<< "$spec"
    if ! docker inspect --format '{{json .NetworkSettings.Networks}}' "http-guard-production-$label" | python3 -c 'import json,sys; sys.exit(0 if "http-guard-production-v6" in json.load(sys.stdin) else 1)'; then
        docker network connect --ip6 "$address" http-guard-production-v6 "http-guard-production-$label"
    fi
done
printf 'Synthetic targets ready: public-a, public-b, private; ports 8090/8443/8444; admin counters 8091.\n'
