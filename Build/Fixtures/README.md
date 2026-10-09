# Genuine TYPO3 integration fixtures

These fixtures install and bootstrap real TYPO3 13.4.36 and 14.3.8 with Guzzle 7.15.5 and 8.2.0. They use the single local extension, including its embedded HTTP Guard kernel. The Core string request entry is decorated by the production extension; the original Core factory remains its inner service. Fresh qualification passes all 168 Composer matrix processes and both classic cells' 84 processes; see the [current verification report](../../Documentation/Development/Verification.rst) for measured results.

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

The wire helper reuses the named containers `http-guard-ext-wire-public` and `http-guard-ext-wire-private` when their image, network address and target ID match. It uses a pinned Python image, an unprivileged user, a read-only filesystem and no Linux capabilities. It does not delete the servers after the matrix. Remove these two containers manually when finished; keep the shared `http-guard-g0-probe` network if another probe still needs it. No production site or external application is modified.

Unit tests can use a prepared runtime's vendor autoloader and PHPUnit executable through `HTTP_GUARD_TEST_AUTOLOAD` and `HTTP_GUARD_PHPUNIT`. These environment variables are test harness seams and cannot select policy or runtime transport inside TYPO3.

Classic mode uses official Core 13.4.36 and 14.3.8 tarballs, both bundling Guzzle 8.2.0 / Promises 3.0.2 / PSR7 3.1.0, and one ZIP-extracted extension. `prepare-classic.py` validates the SHA256-pinned Core downloads and the extension builder's file manifest and creates fresh disposable sites without a Composer command. It needs Python 3.12 or later and PHP with PDO SQLite for the real extension-manager/schema setup. The runner expects an additional two-cell/84-process matrix; the current verification report records its execution. The [original combined packaging proof](../../evidence/packaging/README.md) retains the previous patch versions and SDK tuple as historical evidence.

For extension static analysis with actual Core 14, use `Build/phpstan-typo3.neon` and `Build/phpstan-bootstrap14.php`; `HTTP_GUARD_TEST_AUTOLOAD` may select the prepared Core 14 vendor loader. The incompatible inactive Core 13 declaration is excluded and has a clearly marked static-analysis shell. The shell is not included in the extension ZIP and is never used by genuine Core bootstrap/runtime tests. The embedded kernel is analysed separately with `Build/phpstan-http-guard.neon`.
