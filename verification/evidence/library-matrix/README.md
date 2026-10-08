# Standalone library matrix

All eight tuples ran the complete delivered `tests/Unit` and `tests/Integration` suite in pinned Docker images, on the real synthetic public/private/IPv6/loopback targets. Each passed **122 tests / 2167 assertions, with zero skips, failures or errors**. The logs and JUnit records include all 34 guarded wire cases and all nine DNS/policy/capability/incoming-SAPI cases.

| PHP runtime | Guzzle tuples | libcurl | TLS backend | Result per tuple |
|---|---|---|---|---|
| 8.2.33 | 7.15.5 and 8.2.0 | 8.22.0 | OpenSSL 3.5.8 | 122 / 2167, all green |
| 8.3.33 | 7.15.5 and 8.2.0 | 8.22.0 | OpenSSL 3.5.8 | 122 / 2167, all green |
| 8.4.25 | 7.15.5 and 8.2.0 | 8.22.0 | OpenSSL 3.5.8 | 122 / 2167, all green |
| 8.5.10 | 7.15.5 and 8.2.0 | 8.22.0 | OpenSSL 3.5.8 | 122 / 2167, all green |

All four frozen runtime images report **Alpine Linux 3.24.2** in their captured
`os-release` records.

The G7 tuple pins Promises 2.5.3 and PSR-7 2.13.1; G8 pins Promises 3.0.2 and PSR-7 3.1.0. PHPUnit 11.5.57 runs both locks across the four PHP versions. Minimal dependency audits returned no advisories or abandoned packages. Exact manifests, locks, image references and audit results are under `../../dependencies/`.

For each `phpNNgN` tuple, `.runtime.json` records the actual PHP, full `curl_version()` values and dependency versions; `.os-release.txt` records the Linux distributor; `.image.json` records the frozen image; `.container.json` records the actual created container; `.results.junit.xml` and `.log` record results; `.source-manifest.json` records SHA-256 of the copied source, data, test fixtures, configuration and dependency files. `summary.json` indexes the tuples, and `checksums.json` binds the evidence files.

Run `../../scripts/run-library-matrix.sh` from any location. It accepts optional delivery-root, native-work-directory and evidence-directory arguments; the first defaults relative to the delivered script. Prerequisites are Docker, Bash, Python 3, OpenSSL and rsync. The script installs the frozen locks, validates Composer schema and lock consistency, audits dependencies, prepares owned wire targets idempotently, uses host networking and the deliberate `nss-only-guard.test` hosts entry, then gates every JUnit result on zero skips/errors/failures. Exact fixture pins intentionally suppress only Composer's generic constraint-quality advice (`--no-check-all`); strict schema and lock validation remain enabled.

The DNS truncation responder uses a separate `PHP_BINARY` process and real UDP TC→length-prefixed TCP. This avoids an undeclared `pcntl` test dependency in the pinned images and does not skip that case or alter production source. The first pre-correction attempt exposed the fixture limitation; these are the final complete reruns.

These measured results cover the recorded Linux/Docker runtimes. They do not establish Windows or macOS distributor coverage, production operator pilot results, or independent human release acceptance.
