# Genuine TYPO3 integration fixtures

## Local host and container entry points

Install development tools with `composer install`. With no runtime-selection
flags, the project wrapper uses the installed host PHP and `.Build/vendor/`:

```bash
bash Build/Scripts/runTests.sh -s unit
composer ci:test:php:architecture
composer ci:test:php:fuzz
composer ci:test:php:mutation
composer ci:test:php:performance
```

These invoke real project runners; unavailable tools and failed checks fail.
Mutation thresholds remain enforced and a low score is a failed result. Unit
execution is offline. Architecture, fuzz, Infection and performance measurements
have their own scopes and source-bound results, separate from native wire counts.

For a selected PHP/Core graph, use an isolated checkout and delegate to the
installed `netresearch/typo3-ci-workflows` shared runner:

```bash
bash Build/Scripts/runTests.sh -s unit -p 8.5 -t 13.4.36
bash Build/Scripts/runTests.sh -s unit -p 8.5 -t 14.3.8
```

Core selection delegates require/update to the shared runner's Composer suite;
it creates the initial lock when absent and changes that checkout's development
manifest/lock/vendor. A shared vendor symlink is refused. Do not run it against a shared or
protected checkout. Shorthand `13`/`13.4` selects `^13.4.36`, and `14`/`14.3`
selects `^14.3.8`, preserving safe floors. A successful solver is not runtime proof.
The generic shared runner remains installed under `.Build/vendor/`; it is not
copied into this extension. `runTests.conf` sets the explicit Unit suite and
pinned PHP images. A future PHP test image may be supplied through
`HTTP_GUARD_PHP_IMAGE=<image>@sha256:<digest>`; permission in the production PHP
range does not claim that such a version has already been tested. Before each
selected suite, an installed-runtime probe verifies the actual PHP/Core graph.

## Functional Core entry points

The three executed Core bootstraps live in `Tests/Functional/`: production
decoration and wire contracts, classic package activation, and observe/disabled
mode contracts. The standalone kernel's native tests remain in
`Tests/HttpGuard/Integration/`.

Use an already prepared genuine fixture and the separately prepared controlled
wire targets. These routes do not create targets or install a Core graph:

```bash
bash Build/Scripts/runTests.sh -s integration -f /absolute/fixtures/core14g8
bash Build/Scripts/runTests.sh -s integration -f /absolute/fixtures/classic14 -- classic
bash Build/Scripts/runTests.sh -s classic -f /absolute/fixtures/classic14 -- active
bash Build/Scripts/runTests.sh -s mode -f /absolute/fixtures/core14g8 -- observe
bash Build/Scripts/runTests.sh -s mode -f /absolute/fixtures/classic14 -- disabled classic
```

`classic -- prepare` invokes the existing Core schema preparation
checks on a disposable, initially inactive fixture. `HTTP_GUARD_FIXTURE` remains
a fallback for `-f`; an explicit option takes precedence. Relative fixture paths
resolve from this checkout's root, including when the wrapper is called elsewhere.
Missing loaders, invalid mode/phase arguments and failed Core bootstraps fail.

For `-p`/`-t` selection, the shared container mounts only this checkout, at the
same absolute path. The fixture and its resolved autoloader must therefore be
inside this project mount; external paths and escaping vendor symlinks are
rejected before delegation. Pass a project-contained fixture explicitly:

```bash
bash Build/Scripts/runTests.sh -s integration -f .Build/fixtures/core14g8 -p 8.5 -t 14
```

The wrapper delegates these three suites through the installed official
`suite_http_guard_functional` hook. The hook uses the selected digest-pinned
PHP image in ephemeral host-network containers so that it can reach the
separately prepared controlled Core witnesses. It mounts the physically
validated project, forwards literal fixture arguments, and probes the fixture's
actual PHP/Core versions before executing the entry point. `-t` validates that
installed fixture without changing the development Composer graph. Generic
suites retain the shared runner's existing container routes.

These containers run as the caller's user with a read-only root filesystem,
all capabilities dropped and `no-new-privileges`. Cleanup removes only their
captured immutable IDs. Probe, entry-point and cleanup failures propagate.
Core 13 production fixtures may omit `composer/semver`; the runtime probe then
registers only that namespace from the installed development tool directory.
It keeps Core and SDK loading authoritative to the fixture, without loading
another development Core graph. Missing Semver tools fail explicitly.

The 27 offline routing controls cover validation, literal arguments, container
ownership and failure propagation; they do not establish Core or wire success.
The separately recorded 20 host and 20 selected-container Functional routes
passed with PHP 8.5.11 and 8.5.10 respectively.
Fresh execution results remain bound to their actual fixture/source records.
The installed shared runner's mount syntax requires a checkout path without
whitespace or colons; these fixture routes reject unsupported checkout paths.
Fixture names may contain spaces, but not line breaks.

## Owned native transport targets

Run the native suite through the project orchestrator:

```bash
bash Build/Scripts/runTests.sh -s native
# Explicit selected container graph, in an isolated checkout:
bash Build/Scripts/runTests.sh -s native -p 8.5 -t 14.3.8
```

Each invocation owns uniquely named and labelled containers/networks, captures
immutable IDs at creation and removes only those IDs on success, failure or
interrupt. It holds a local-user lock throughout preparation, execution and
teardown. The existing PHP wire cases retain their fixed synthetic IPs; Docker
IPAM refuses overlapping subnets. A pre-existing foreign subnet or occupied
loopback port causes failure. The runner neither adopts nor deletes another
run's targets, including older `http-guard-production-*` containers.

Synthetic TLS keys are generated per run and removed at teardown. The tests'
certificate path is overlaid with a read-only container bind mount; existing
repository certificates are neither reused nor changed. Logs, JUnit, image
metadata and the actual PHP/Core/SDK tuple remain under `.Build/runtime/native.*`.
The tests use the pinned PHP image and Docker host networking for the controlled
bridge/loopback destinations. These Linux Docker fixtures are local test inputs,
not production endpoints. Runs on the same Docker daemon serialize their fixed
address resources; parallel use of the same counters is unsupported.

`HTTP_GUARD_TEST_AUTOLOAD` and `HTTP_GUARD_PHPUNIT` may select a genuine installed
SDK/Core graph. The container inspects actual PHP/Core versions before executing;
an explicit selection mismatch fails. An external vendor directory is mounted
read-only, not changed. The selected graph and native cURL capabilities must also
pass the production/test API checks.

`bash Build/Scripts/runTests.sh -s mutation-native` uses the same owned targets
for the combined Unit/native Infection scope in `infection.native.json5`.
It enables coverage and uses one thread because wire counters are shared within
the run. All production `Classes/`, default mutators and both 90% thresholds
remain in scope; a lower score propagates as failure. This optional longer
measurement is separate from the offline mutation entry point and from fuzzing.

## Genuine Core bootstrap and classic installation

These fixtures install and bootstrap real TYPO3 13.4.36 and 14.3.8 with Guzzle 7.15.5 and 8.2.0. They use the single local extension, including its embedded HTTP Guard kernel. The Core string request entry is decorated by the production extension; the original Core factory remains its inner service. The recorded qualification passes 168 Composer matrix processes and 84 classic processes, each bound to its documented source snapshot; see the [verification report](../../Documentation/Development/Verification.rst) for measured results.

Use a native Linux runtime directory, PHP with curl/curl-multi, Composer and Docker. The recorded execution used PHP 8.5.11 and libcurl 8.5.0. The two Docker networks are internal. Their target servers use synthetic public `203.0.114.102:8080` and private `10.23.5.12:8080` addresses; the policy allows the private address only through the explicitly bound endpoint. The subnet choices must be available locally. Do not run unrelated traffic against these targets while collecting counters.

From this package directory:

```sh
Build/Fixtures/prepare-wire.sh
python3 Build/Fixtures/prepare.py --runtime /absolute/native/typo3-fixtures
python3 Build/Fixtures/run-matrix.py --runtime /absolute/native/typo3-fixtures --evidence /absolute/new/evidence
```

The patched Core releases require SVG sanitizer 1.0.0. Active preparation preserves Composer's security blocking and has no advisory-exception option. Fresh resolution of all four exact full Core/SDK fixture graphs reports zero vulnerability advisories. Core 13 still requires the upstream abandoned `doctrine/annotations` package; vulnerability audit uses `--abandoned=report` to retain that maintenance warning while failing on any vulnerability advisory. Core 14 also passes the default strict audit. See [the current dependency report](../../Documentation/Development/Dependencies.rst).

Preparation reads the active `Build/Fixtures/core13g7`, `core13g8`, `core14g7` and `core14g8` Composer manifests, binds their one extension path repository to the current checkout, and resolves the exact Core/Guzzle/Promises/PSR7 tuples freshly. Historical lock files are preserved byte-for-byte in explicit ZIP archives under `evidence/`; they are never installation inputs. Fresh preparation is preferred. If reusing a runtime after changing extension registration or service definitions, clear both the Core system cache and DI cache before bootstrapping. A runtime pointing at a different fixture configuration is refused.

The runner executes 168 processes: four enforce wire runs with 35 assertions each, eight observe/disabled wire runs, 152 actual TYPO3 CLI commands, and four ordinary startup denials. These are runner expectations; the current verification report records the actual successful processes and assertions. The CLI and startup-denial records compare independently instrumented TCP accepts and HTTP request counts before and after. Counter polling is excluded from target counts. The shared normative `EP-PRIVATE-UNBOUND` record is loaded from the embedded kernel corpus and records its case ID/hash plus zero native handler construction for the denied unbound request. A flat legacy Vault allowlist entry exists alongside the Core context lists without creating a new Public grant.

The legacy standalone Core wire helper reuses the named containers `http-guard-ext-wire-public` and `http-guard-ext-wire-private` when their image, network address and target ID match. It uses a pinned Python image, an unprivileged user, a read-only filesystem and no Linux capabilities. It does not delete the servers after the matrix. This legacy helper is separate from the per-run native orchestrator above; do not combine their resources. Remove these two containers manually when finished; keep the shared `http-guard-g0-probe` network if another probe still needs it. No production site or external application is modified.

Unit tests can use a prepared runtime's vendor autoloader and PHPUnit executable through `HTTP_GUARD_TEST_AUTOLOAD` and `HTTP_GUARD_PHPUNIT`. These environment variables are test harness seams and cannot select policy or runtime transport inside TYPO3.

Classic mode uses official Core 13.4.36 and 14.3.8 tarballs, both bundling Guzzle 8.2.0 / Promises 3.0.2 / PSR7 3.1.0, and one ZIP-extracted extension. `prepare-classic.py` validates the SHA256-pinned Core downloads and the extension builder's file manifest and creates fresh disposable sites without a Composer command. It needs Python 3.12 or later and PHP with PDO SQLite for the real extension-manager/schema setup. The runner expects an additional two-cell/84-process matrix; the current verification report records its execution. The [original combined packaging proof](../../evidence/packaging/README.md) retains the previous patch versions and SDK tuple as historical evidence.

For extension static analysis with actual Core 14, use `Build/phpstan-typo3.neon` and `Build/phpstan-bootstrap14.php`; `HTTP_GUARD_TEST_AUTOLOAD` may select the prepared Core 14 vendor loader. The incompatible inactive Core 13 declaration is excluded and has a clearly marked static-analysis shell. The shell is not included in the extension ZIP and is never used by genuine Core bootstrap/runtime tests. The embedded kernel is analysed separately with `Build/phpstan-http-guard.neon`.
