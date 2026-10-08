# Reproducing the evidence

Use a native Linux directory for dependency installations. The source delivery contains no vendor trees. Docker, Composer, Bash, Python 3, rsync and OpenSSL are needed for the owned synthetic fixtures. Actual denied attempts must be checked against native construction and independent target TCP/HTTP counters, not only against the resulting exception.

## Library

`packages/http-guard/tests/Integration/prepare-wire.sh` creates isolated synthetic public/private/IPv6 networks and four servers, including a host-network loopback server. The certificates and keys are labeled test-only. Its traffic targets are owned containers. The admin ports are uncounted; target TCP accepts and HTTP requests are counted independently, including failed TLS handshakes. Do not run independent counter assertions concurrently or restart a server during a test run.

Use `scripts/run-library-matrix.sh` with the shipped Guzzle-7/Guzzle-8 manifests and lockfiles. It copies final source, data and tests into isolated native runtimes and executes all tests under the four recorded PHP image digests. The NSS-only fixture uses an explicit `nss-only-guard.test:10.23.4.12` hosts entry: the controlled DNS resolver must still deny that name when it has no verified answer. Loopback and IPv6 wire cases require native Linux networking; a machine that cannot reach the fixture has not passed those cases.

The missing-curl, missing-curl-multi, changed Composer metadata and option-set-failure probes execute in separate processes. The incoming Proxy-header case uses a real child SAPI and records both received-header and process-environment behavior. Production functions or dependency metadata are never altered by the ordinary tests.

The minimal package can also run its policy and sanitizer tests with its own dependency installation:

```sh
composer install --working-dir=packages/http-guard
composer --working-dir=packages/http-guard analyse
packages/http-guard/vendor/bin/phpunit --configuration packages/http-guard/phpunit.xml --testsuite 'HTTP Guard'
```

The complete testsuite requires the prepared wire fixture and the NSS hosts entry. Use the matrix script for the complete acceptance run. A standalone Composer installation may choose either permitted tuple; the runtime rejects mixed majors even if Composer resolves individual OR constraints.

## Mutations

`scripts/run-mutations.py` changes only disposable copies through `php-ast-edit`; it never changes the production tree. Supply absolute paths to the package, scratch/evidence directories, each installed minimal vendor directory, the editor and the qualified PHP image. Each vendor directory must include PHPUnit. For example:

```sh
python3 verification/scripts/run-mutations.py \
  --package packages/http-guard \
  --scratch /tmp/http-guard-mutations \
  --evidence /tmp/http-guard-mutation-evidence \
  --g7-vendor /tmp/http-guard-matrix/guzzle7/vendor \
  --g8-vendor /tmp/http-guard-matrix/guzzle8/vendor \
  --editor /absolute/path/to/php-ast-edit \
  --image ghcr.io/typo3/core-testing-php85@sha256:53df750b7e68ccce8da03a03bfe7cb57d732308b932f2ea92b1fd31679626bc6
```

All twelve selected runs must fail with recorded wire/native witnesses: removed address check, removed pin, enabled resolution fallback, proxy passthrough, stream passthrough and removed endpoint method binding, on both Guzzle majors. The summary records killed mutants and binds them to the unchanged production source hashes. A tooling failure without a witness is not a killed mutant.

## TYPO3 and Vault

See the extension's `Build/Fixtures/README.md` for the actual Core installation/bootstrap matrix and its `Build/Scripts/runTests.sh`. The four fixture locks and source hashes are preserved. Earlier AP-01/G0 probes are retained separately; their historical prototype is not the final production library.

Apply the Vault patch only after `git apply --check` succeeds on its exact base commit. Use the receiving repository's `Build/Scripts/runTests.sh` and the supplied adapter-test wrapper. Its full Unit/Fuzz/Functional/static evidence and the actual resource/token/stream/cancel cases are separate from the standalone library matrix. Each new adapter run records the exact copied library source manifest.

The release ledger distinguishes executed checks, implementation, measurement limits and external acceptance. A successful synthetic run does not perform the human G6 review or the operator pilot.
