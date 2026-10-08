#!/usr/bin/env bash
# Reproduce the eight standalone-library tuples with frozen images and locks.
set -euo pipefail
matrix_script_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
matrix_delivery="${1:-${HTTP_GUARD_DELIVERY_ROOT:-$(cd "$matrix_script_dir/../.." && pwd -P)}}"
matrix_native="${2:-${HTTP_GUARD_MATRIX_WORK:-$(mktemp -d /tmp/http-guard-matrix-XXXXXX)}}"
matrix_evidence="${3:-${HTTP_GUARD_MATRIX_EVIDENCE:-$matrix_delivery/verification/evidence/library-matrix}}"
matrix_package="$matrix_delivery/packages/http-guard"
matrix_dependencies="$matrix_delivery/verification/dependencies"
mkdir -p "$matrix_native" "$matrix_evidence"
cp "$matrix_dependencies/php-images.json" "$matrix_evidence/php-images.json"
bash "$matrix_package/tests/Integration/prepare-wire.sh" > "$matrix_evidence/fixture-prepare.log" 2>&1
docker version > "$matrix_evidence/docker-version.txt"
matrix_image_for() {
    python3 -c 'import json,sys; print(json.load(open(sys.argv[1]))[sys.argv[2]]["reference"])' "$matrix_dependencies/php-images.json" "$1"
}
matrix_install_image="$(matrix_image_for 82)"
# Exact tuple pins are deliberate fixtures; skip only Composer's constraint-quality advice.
for matrix_major in 7 8; do
    matrix_directory="$matrix_native/guzzle$matrix_major"
    mkdir -p "$matrix_directory"
    for matrix_part in src data tests; do
        mkdir -p "$matrix_directory/$matrix_part"
        rsync -a --delete "$matrix_package/$matrix_part/" "$matrix_directory/$matrix_part/"
    done
    cp "$matrix_package/phpunit.xml" "$matrix_package/phpstan.neon" "$matrix_directory/"
    cp "$matrix_dependencies/guzzle$matrix_major.composer.json" "$matrix_directory/composer.json"
    cp "$matrix_dependencies/guzzle$matrix_major.composer.lock" "$matrix_directory/composer.lock"
    docker run --rm --user "$(id -u):$(id -g)" \
        -e COMPOSER_HOME=/library/.composer-home -e COMPOSER_CACHE_DIR=/library/.composer-cache -e COMPOSER_ROOT_VERSION=0.1.0-dev \
        -v "$matrix_directory:/library" -w /library "$matrix_install_image" \
        sh -ec 'composer install --no-interaction --no-plugins --no-scripts; composer validate --strict --no-check-all; composer audit --format=json' \
        > "$matrix_evidence/guzzle$matrix_major.dependencies.log" 2>&1
    python3 - "$matrix_directory" "$matrix_evidence/guzzle$matrix_major.source-manifest.json" <<'PY'
import hashlib,json,sys
from pathlib import Path
root=Path(sys.argv[1]); paths=[]
for tree in ('src','data','tests'):
    paths.extend(p for p in (root/tree).rglob('*') if p.is_file())
paths.extend(root/p for p in ('composer.json','composer.lock','phpunit.xml','phpstan.neon'))
records={str(p.relative_to(root)):hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(paths)}
Path(sys.argv[2]).write_text(json.dumps({'algorithm':'sha256','files':records},indent=2)+'\n')
PY
done
matrix_container=''
matrix_cleanup() {
    if [[ -n "$matrix_container" ]]; then
        docker rm -f "$matrix_container" >/dev/null 2>&1 || true
    fi
}
trap matrix_cleanup EXIT
for matrix_php in 82 83 84 85; do
    matrix_image="$(matrix_image_for "$matrix_php")"
    if ! docker image inspect "$matrix_image" >/dev/null 2>&1; then
        docker pull "$matrix_image" > "$matrix_evidence/php$matrix_php.pull.log" 2>&1
    fi
    docker image inspect "$matrix_image" > "$matrix_evidence/php$matrix_php.image.json"
    for matrix_major in 7 8; do
        matrix_name="php${matrix_php}g${matrix_major}"
        matrix_directory="$matrix_native/guzzle$matrix_major"
        matrix_container="$(docker create --network host --add-host nss-only-guard.test:10.23.4.12 \
            --user "$(id -u):$(id -g)" \
            -v "$matrix_directory:/library" -w /library \
            "$matrix_image" sh -ec '
                php -r '\''require "vendor/autoload.php"; $packages=[]; foreach (["guzzlehttp/guzzle","guzzlehttp/promises","guzzlehttp/psr7","phpunit/phpunit"] as $p) {$packages[$p]=Composer\InstalledVersions::getPrettyVersion($p);} echo json_encode(["php"=>PHP_VERSION,"os"=>PHP_OS,"osFamily"=>PHP_OS_FAMILY,"curl"=>curl_version(),"packages"=>$packages],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR),"\n";'\'' > runtime.json
                cat /etc/os-release > os-release.txt
                php vendor/bin/phpunit --configuration phpunit.xml --log-junit results.junit.xml
            ')"
        set +e
        docker start -a "$matrix_container" > "$matrix_evidence/$matrix_name.log" 2>&1
        matrix_result="$(docker inspect --format '{{.State.ExitCode}}' "$matrix_container")"
        set -e
        docker inspect "$matrix_container" > "$matrix_evidence/$matrix_name.container.json"
        for matrix_artifact in runtime.json os-release.txt results.junit.xml; do
            if [[ -f "$matrix_directory/$matrix_artifact" ]]; then
                cp "$matrix_directory/$matrix_artifact" "$matrix_evidence/$matrix_name.$matrix_artifact"
            fi
        done
        cp "$matrix_evidence/guzzle$matrix_major.source-manifest.json" "$matrix_evidence/$matrix_name.source-manifest.json"
        docker rm "$matrix_container" >/dev/null
        matrix_container=''
        printf '%s exit=%s\n' "$matrix_name" "$matrix_result"
        if [[ "$matrix_result" != 0 ]]; then
            tail -n 60 "$matrix_evidence/$matrix_name.log"
            exit "$matrix_result"
        fi
        python3 - "$matrix_evidence/$matrix_name.results.junit.xml" <<'PY'
import sys,xml.etree.ElementTree as ET
root=ET.parse(sys.argv[1]).getroot()
for suite in root.iter('testsuite'):
    for key in ('failures','errors','skipped'):
        if int(suite.get(key,'0')):
            raise SystemExit('Matrix gate refused nonzero '+key)
PY
    done
done
printf 'All eight matrix tuples passed without skips. Evidence: %s\n' "$matrix_evidence"
