# Current CI and governance verification

The new workflows and project guidance are configured in the current review branch. The initial measurements below record local executions; subsequent source-bound [GitHub runs](../remote/README.md) are recorded separately. Neither constitutes production approval. `ci-summary.json` lists the exact measured results and remaining failures. `source-hashes.json` identifies the source and tooling bytes copied into the actual native runtime; `runtime-source-hash-comparison.exit` is zero.

The current combined Unit and actual native HTTP/TLS/mTLS/DNS/invocation suite passes 145 tests and 2,253 assertions, measuring 80.80% line coverage. Genuine Core Bootstrap/CLI process probes are separately qualified and remain outside this coverage report. Branch coverage was not measured.

PHPStan 2.3.1 at level8 passes the G7 kernel and actual Core13 extension. The actual Core14 extension also passes. The G8 kernel reports five missing HandlerStack generic annotations and one redundant callable assertion. The cleanup capability assertion has runtime significance; it was not removed to satisfy a static heuristic. No suppressions, fallback transports or production behavior changes were applied for these findings.

A fresh resolution within the qualified Core/SDK constraints still selects enshrined/svg-sanitize0.22.0. The strict no-plugin audit returns exit1 for three known upstream advisories. Disposable functional test manifests except only those three documented IDs. The strict audit removes all exceptions and remains red. An unavailable advisory service also fails the audit. Historical locks are preserved unchanged.

Local `actionlint` and offline `zizmor` both exit 0. The explicit pinning policy permits the reviewed Netresearch reusable workflow paths to follow `main`; every third-party action is pinned to a reviewed full SHA. No secret inheritance exists. The coverage upload job receives only the current main-run XML artifact and never checks out or executes repository code. An actual Codecov upload remains unverified. No PHP CodeQL coverage is claimed; its workflow targets supported Python tooling and GitHub Actions only.

Source PHP lint, Python compilation, strict Composer validation and two identical deterministic128-member ZIP builds pass. The package builder includes only runtime assets, normative policy corpus and bilingual documentation; it excludes test fixtures, private synthetic keys, historical evidence and vendor dependencies.

Local reproduction from a disposable exact dependency fixture:

```sh
vendor/bin/phpunit --configuration phpunit.xml --testsuite Unit
vendor/bin/phpstan analyse --configuration Build/phpstan-http-guard.neon --no-progress
HTTP_GUARD_TEST_AUTOLOAD="$PWD/vendor/autoload.php" vendor/bin/phpstan analyse --configuration Build/phpstan-typo3-core13.neon --no-progress
HTTP_GUARD_TEST_AUTOLOAD="$PWD/vendor/autoload.php" vendor/bin/phpstan analyse --configuration Build/phpstan-typo3.neon --autoload-file Build/phpstan-bootstrap14.php --no-progress
actionlint .github/workflows/*.yml
zizmor --offline .github/workflows
python3 Build/Scripts/build-extension.py --output /tmp/nr_http_guard_0.1.0.zip
```

Select the Core13 or Core14 analysis command to match the actual fixture. Native integration requires the synthetic target preparation described in the repository and serial access to its counters. The committed workflow contains the full fixture setup, exact version selections, cleanup and artifact commands.

The [secret scanning and SAST triage](secret-and-sast-triage.md) records the exact verified history fingerprints and the remaining unverified archival cookie. It preserves all 13 fixture/tooling SAST findings and their applicability decisions without blanket exclusions.

Complete matrix execution, an actual coding-style gate, broader Infection MSI, independent human G6, the operator pilot and published release provenance remain separate open work. This agent review does not substitute for G6.
