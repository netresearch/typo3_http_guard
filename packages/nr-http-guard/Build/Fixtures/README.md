# Genuine TYPO3 integration fixtures

These fixtures install and bootstrap real TYPO3 13.4.35 and 14.3.7 with Guzzle 7.15.5 and 8.2.0. They use the local sibling library and this extension. They do not replace the Core bootstrap or request factory with a test stub. The Core string request entry is decorated by the production extension; the original Core factory remains its inner service.

Use a native Linux runtime directory, PHP with curl/curl-multi, Composer and Docker. The recorded execution used PHP 8.5.11 and libcurl 8.5.0. The two Docker networks are internal. Their target servers use synthetic public `203.0.114.102:8080` and private `10.23.5.12:8080` addresses; the policy allows the private address only through the explicitly bound endpoint. The subnet choices must be available locally. Do not run unrelated traffic against these targets while collecting counters.

From this package directory:

```sh
Build/Fixtures/prepare-wire.sh
python3 Build/Fixtures/prepare.py --runtime /absolute/native/typo3-fixtures
python3 Build/Fixtures/run-matrix.py --runtime /absolute/native/typo3-fixtures --evidence /absolute/new/evidence
```

Composer may refuse the historical exact Core patches because their SVG-sanitize dependency has three published advisories. Preparation does not suppress them automatically. To reproduce this isolated fixture evidence with those exact versions after deciding to accept the disposable test scope, use `--allow-disposable-fixture-advisories`. This flag adds only the three recorded advisory IDs to the disposable fixture manifest. It does not change either production package, approve these dependencies for deployment, or widen the tested Core range. See [the evidence report](../../../../evidence/typo3-integration/README.md).

Preparation copies the recorded dependency locks and rewrites the two local path repositories to their current checkout. A targeted Composer update refreshes only local package metadata and their dependencies; exact Core and Guzzle direct constraints stay fixed. Fresh preparation is preferred. If reusing a runtime after changing extension registration or service definitions, clear both the Core system cache and DI cache before bootstrapping. A runtime pointing at a different fixture configuration is refused.

The matrix runs 168 processes: four enforce wire runs with 35 assertions each, eight observe/disabled wire runs, 152 actual TYPO3 CLI commands, and four ordinary startup denials. The CLI and startup-denial records compare independently instrumented TCP accepts and HTTP request counts before and after. Counter polling is excluded from target counts. The shared normative `EP-PRIVATE-UNBOUND` record is loaded from the sibling library corpus and records its case ID/hash plus zero native handler construction for the denied unbound request. A flat legacy Vault allowlist entry exists alongside the Core context lists without creating a new Public grant.

The wire helper reuses the named containers `http-guard-ext-wire-public` and `http-guard-ext-wire-private` when their image, network address and target ID match. It uses a pinned Python image, an unprivileged user, a read-only filesystem and no Linux capabilities. It does not delete the servers after the matrix. Remove these two containers manually when finished; keep the shared `http-guard-g0-probe` network if another probe still needs it. No production site or external application is modified.

Unit tests can use a prepared runtime's vendor autoloader and PHPUnit executable through `HTTP_GUARD_TEST_AUTOLOAD` and `HTTP_GUARD_PHPUNIT`. These environment variables are test harness seams and cannot select policy or runtime transport inside TYPO3.
