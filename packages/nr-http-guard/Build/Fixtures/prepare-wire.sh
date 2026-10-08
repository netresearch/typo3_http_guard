#!/usr/bin/env bash
set -euo pipefail
fixture_dir=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
image=python@sha256:f85c5697265c178cc6887276c55fe16cf3d14ca35c3df6a5eab3b360534a55d2
public_network=http-guard-g0-probe
private_network=http-guard-ext-private
if ! docker network inspect "$public_network" >/dev/null 2>&1; then
    docker network create --internal --subnet 203.0.114.0/24 "$public_network"
fi
if ! docker network inspect "$private_network" >/dev/null 2>&1; then
    docker network create --internal --subnet 10.23.5.0/24 "$private_network"
fi
for target in public private; do
    if [[ $target == public ]]; then
        name=http-guard-ext-wire-public
        network=$public_network
        address=203.0.114.102
        wire_id=U
    else
        name=http-guard-ext-wire-private
        network=$private_network
        address=10.23.5.12
        wire_id=P
    fi
    if docker inspect "$name" >/dev/null 2>&1; then
        existing_address=$(docker inspect --format "{{with index .NetworkSettings.Networks \"$network\"}}{{.IPAddress}}{{end}}" "$name")
        existing_id=$(docker inspect --format '{{range .Config.Env}}{{if or (eq . "WIRE_ID=P") (eq . "WIRE_ID=U")}}{{.}}{{end}}{{end}}' "$name")
        existing_image=$(docker inspect --format '{{.Config.Image}}' "$name")
        if [[ $existing_address != "$address" || $existing_id != "WIRE_ID=$wire_id" || $existing_image != "$image" ]]; then
            echo "Existing fixture container does not match recorded address, ID or image: $name" >&2
            exit 1
        fi
    else
        docker run --detach --name "$name" --network "$network" --ip "$address" \
            --read-only --cap-drop ALL --user 65534:65534 \
            --mount "type=bind,src=$fixture_dir/wire_server.py,dst=/wire_server.py,readonly" \
            --env "WIRE_ID=$wire_id" "$image" python /wire_server.py
    fi
done
