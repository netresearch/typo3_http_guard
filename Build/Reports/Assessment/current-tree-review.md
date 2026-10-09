# Independent review of repository convention changes

Reviewed branch: `chore/repository-conventions`, including the working tree, against `7a3a39bbaa763eb876ff4c9cdc743b689c557590`.

## Verdict

The documentation, metadata and repository-convention changes are reviewable as an alpha/draft PR. No newly introduced runtime correctness or security defect was found in the production PHP changes. The current verification gates are not all green, and the assessment must not be presented as having closed every applicable gap or as release approval.

The main documentation is English, the optional German localization remains under `Documentation/Localization.de_DE`, and `docs/` is absent. Historical source and execution claims are explicitly bound to their original revision. The new assessment separates mechanical outcomes, model judgments, applicability decisions, actual runtime coverage and human acceptance gates. Exact TYPO3/SDK runtime qualifications remain unchanged; the PHP metadata ceiling now matches the recorded 8.2–8.5 range.

## Open findings and gates

1. **[P1] Fresh kernel static analysis remains failing on the qualified Guzzle 8 tuple.** The current job at `.github/workflows/ci.yml:46` runs the kernel checker without suppression. Actual PHPStan 2.3.1 execution reports five missing `HandlerStack` generic annotations and one `is_callable` narrowing diagnostic in `TransferLease`. These are existing source findings exposed by the new gate, not a demonstrated runtime regression caused by the header/documentation edits. They remain unresolved and are accurately described in `Documentation/Development/Assessment.rst:108`. The PR must retain the failed status and not claim the whole CI matrix passes. Evidence: `work/assessment/ci-final/kernel-g8-phpstan-2.3.1-diagnostics.log`.

2. **[P1] Fresh production dependency audit remains failing.** The unchanged qualified Core/SDK constraints resolve `enshrined/svg-sanitize` 0.22.0 with the three recorded advisories. The strict audit at `.github/workflows/security.yml:84` removes assembly exceptions before auditing and preserves the result. This is an existing dependency/release gate, not a new Guard dependency. It must remain open before production/release acceptance. Evidence: `work/assessment/ci-current-audit.json`.

3. **[P2] Coding-style recommendations remain open.** Syntax linting and the existing level-8 analysis do not establish full PSR-12 conformance. The existing formatting finding is now explicitly retained in `Documentation/Development/Assessment.rst:133`. A full style correction is separate from the reviewable prose/header changes.

Independent human security review, an actual operator pilot, the named CI performance reference and broader Infection mutation measurement remain distinct uncompleted acceptance work. The 18 targeted security mutants are recorded historical evidence and are not a project-wide mutation score.

## Review findings resolved during this pass

- The inherited reusable Composer audit could exit successfully after an advisory-service outage. The caller now skips that audit and runs an independent strict audit whose nonzero result, including an unavailable service, fails the job.
- Dedicated native-wire verification now runs the combined Unit/integration suite against all three qualified SDK tuples, preserves fresh coverage and uses isolated main-branch-only coverage upload credentials. The upload job executes no repository code.
- `.env` and `.env.*` are ignored while `.env.example` can remain tracked.
- Missing SPDX headers in `Configuration/Services.php` and the Core 14 analysis bootstrap were added without runtime changes.
- The 32-character RST heading conflicted with the configured conflict-marker length. `Documentation/**` now uses a marker length of 128, and the final `git diff --check` passes.
- The German installation requirements now retain the PHP 8.5 ceiling.
- The newly bundled unchanged SVG logo's CC-BY-SA-4.0 license is explicitly mapped in the package license notices, alongside the GPL extension and MIT kernel.

## Verification inspected

- Eleven baseline mechanical batches: 755 raw results, exact sums verified, no blocked checks. The completed native security run verifies all 1,912 baseline source hashes and leaves its snapshot unchanged. Raw results and applicability review are in this directory.
- Combined fresh Unit/native-wire execution: 145 tests, 2,253 assertions, no failures; genuine Core 14 static analysis passes. The kernel Guzzle 8 static check remains failing as recorded above.
- Genuine Core 13 level-8 analysis records all three qualified SDK tuples with zero errors and explicitly excludes the incompatible inactive Core declaration through an analysis-only shell. That shell is not in the runtime ZIP or genuine Core runtime tests.
- Actionlint passes; Zizmor returns no findings with explicit Netresearch reusable-workflow pinning policy. Third-party actions use full commit SHA references, caller permissions match the recorded reusable contracts, and dependency resolution/SBOM tooling runs with plugins and scripts disabled.
- Existing production header AST evidence covers 74 files without semantic mismatches. The two later header additions are comment-only and were inspected separately; an updated combined AST record is expected from the implementation agent before finalization.
- English and German renderer logs report 16 and 13 pages without warnings; the independent validator reports 29 RST files and zero warnings. Later prose edits should receive the implementation agent's final render before final delivery.
- Two recorded package builds produce the same archive hash and 128 members, exclude development/evidence/vendor material and include the localized manual and canonical icon. These recorded builds precede the final additional header/license-note edits; the final delivery archive must be rebuilt from the finished tree.

This review did not alter source, remotes, commits or external messages. Hosted GitHub Actions execution is separate from the inspected local execution evidence.
