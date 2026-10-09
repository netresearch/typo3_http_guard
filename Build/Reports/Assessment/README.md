# Repository assessment evidence

The initial source assessment is bound to commit
`7a3a39bbaa763eb876ff4c9cdc743b689c557590`. `baseline/` contains raw official
mechanical checkpoint results, actual agent reviews and calibrated findings.
Raw pass/fail/skip counts are kept separate from applicable project findings.

The frozen baseline's combined Unit and kernel integration suite passes
145 tests with 2,253 assertions. Measured line coverage is 80.80%; Unit-only
coverage is 57.25%. Genuine TYPO3 bootstrap and classic-installation matrices
are separate historical evidence and are not included in these percentages.

`core13-static/` records genuine PHPStan level 8 execution against all three
qualified TYPO3 13.4.35 SDK tuples. `header-edits/` verifies that license-header
edits preserve the production AST. `documentation/` records warning-free
English and German rendering and the independent documentation validator.

These are development records, excluded from the installable extension ZIP.

`post/` preserves the full 135-check conformance/readiness rerun and one
targeted metadata follow-up. The merged outcome is 110 pass, 24 fail and one
skip; remaining failures are calibrated rather than suppressed. `current/`
contains fresh execution, dependency audit, workflow validation and the final
ZIP member manifest. Package hashes in the earlier CI summary describe that
earlier review build; `final-extension-archive-manifest.json` describes the
final reviewed ZIP.

GitHub's 36 medium dependency alerts repeat three unique SVG advisories in
twelve archived fixture locks. The current strict audit records those three
unique advisories separately. Repository and organization secret-name checks
confirm the existing coverage upload credential is available; the remote
coverage service and upload still require execution.

They are not human security approval, a production pilot, a SLSA certification
or proof of a future release. The manual's
[assessment](../../../Documentation/Development/Assessment.rst) explains the
scope and remaining gates.
