# Combined-extension kernel matrix

The production package is the single root TYPO3 extension `netresearch/nr-http-guard`. Its kernel is included in `Classes/HttpGuard`, data in `Resources/Private/HttpGuard/data`, and tests in `Tests/HttpGuard`. No separate library installation is required.

All **12** actual Docker cells passed **126 tests / 2180 assertions each**, with **zero skips, failures or errors**. Four PHP runtimes (8.2.33, 8.3.33, 8.4.25 and 8.5.10) each ran these exact tuples:

| Fixture | Guzzle | Promises | PSR-7 |
|---|---|---|---|
| Latest verified G7 | 7.15.5 | 2.5.3 | 2.13.1 |
| Verified G8 | 8.2.0 | 3.0.2 | 3.1.0 |
| Official Core/TER classic G7 | 7.15.3 | 2.5.2 | 2.13.0 |

Mixed tuple combinations remain fail-closed and are covered by the additional isolated RuntimeSupport tests. The suite includes all 34 controlled wire cases, nine DNS/capability/SAPI cases and the real UDP TC→TCP responder test without pcntl. The four pinned images used by these 12 cells report Alpine Linux 3.24.2, libcurl 8.22.0 and OpenSSL 3.5.8. Every minimal test fixture audit reports no advisories or abandoned packages.

`summary.json` indexes runtime, JUnit, logs and source manifests for every cell. Source manifests retain the combined layout; `production-composer.json` is the root production manifest and `composer.json` is explicitly the kernel-only verification project. The test fixtures under `../../dependencies/combined-kernel/` omit TYPO3/Symfony dependencies to test the independent kernel on PHP8.2–8.5. They are not production library packages. Exact image references remain in the recorded `php-images.json`.

The portable command is `../../scripts/run-library-matrix.sh [extension-root] [native-work-directory] [evidence-directory]`. Docker, Bash, Python3, OpenSSL and rsync are required. The runner uses pinned dependencies/images, the actual combined filesystem layout and exclusive synthetic wire counters. It validates Composer schema and lock consistency while suppressing only generic quality warnings about the intentionally exact test constraints.

Earlier two-package, eight-cell records remain immutable historical evidence under `../library-matrix/`. They do not substitute for these new 12-cell combined-extension records. These synthetic Linux results do not establish operator production pilot or independent human release acceptance.
