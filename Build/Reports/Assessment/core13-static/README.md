# Genuine TYPO3 13 static analysis

`Build/phpstan-typo3-core13.neon` checks the extension adapter at PHPStan
level 8 against real TYPO3 13.4.35 dependencies. Its bootstrap rejects a
different Core revision or a readonly Core RequestFactory. The active
`GuardedRequestFactory13` is loaded from production source. Only the inactive,
incompatible Core 14 declaration receives a clearly marked analysis shell
under `Build/phpstan-bootstrap13.php`.

The embedded kernel is excluded because its separate configuration covers it.
No baseline, ignoreErrors entries, annotations that suppress errors or fake
Core 13 package are used. Build files are excluded from extension ZIP packaging.

From the repository root, a CI runtime containing actual Core 13 dependencies
can invoke:

```bash
HTTP_GUARD_TEST_AUTOLOAD="$PWD/vendor/autoload.php" \
    vendor/bin/phpstan analyse \
    --configuration Build/phpstan-typo3-core13.neon \
    --no-progress
```

The recorded local runs use PHP 8.5.11 and PHPStan 2.3.1, with the already
installed genuine Core 13 vendors for each of the three exact SDK tuples.
The additional `--debug` runs explicitly list every analysed adapter source
file and rule out treating a previous vendor's cached result as fresh evidence.
`summary.json` records exact versions, origins, results, analysis file paths,
configuration hashes and all production PHP hashes.

The bootstrap PHP file was created by the required AST tool. Its parser and
host lint passed; no configured application verification ran in that tool.
Actual PHPStan results are separate evidence, as recorded in this directory.

# English manual review

The read-only technical review covers all 13 pages preserved in
`Documentation/Localization.de_DE/`. `manual-structural-comparison.json`
confirms exact anchors, reference targets, code blocks, literalinclude targets
and public API directives. Configuration fields, types and default values are
equivalent; five textual "no default; required" annotations are translated from
their German labels. The prose
review found no translation defect in:

- Exact Core and complete SDK tuple restrictions, classic metadata loading,
  proxy rejection and offline diagnostic boundaries.
- Default enforce mode, strict schema, narrow endpoint networks, loopback
  rules, mapped IPv6, metadata denials and operator precedence.
- Raw URI information loss, Public Fetch credentials, PSR-18 redirect/error
  semantics, permitted SDK options and streaming/cancellation ownership.
- Complete DNS chains, truncation retry, NSS exclusion, the documented
  sequential 81-second bound and absence of an application-wide deadline.
- Immutable snapshots, expiry versus review warnings, worker restart and
  revocation limits; log redaction, counters and deliberate rollback.
- Separate optional Vault migration, licenses and retained original ADRs.

Package metadata now caps PHP below 8.6. The root owner was advised to state
the extension's PHP 8.2–8.5 package range explicitly in Installation, separately
from the embedded core's PHP 8.2 syntax lower bound. Manual files were not
changed by this QA task. Rendering is owned by the root agent.
