# Independent baseline execution review

Baseline commit: `7a3a39bbaa763eb876ff4c9cdc743b689c557590`.

The original mounted-filesystem `mechanical/security-audit.raw.json` is empty and is not an execution result. The accompanying `security-catalog-mechanical.json` contains checkpoint definitions, not outcomes. Both original files were preserved.

The security checkpoint runner was completed against a native Linux copy of the existing frozen baseline. The copy retains Git history, the exact HEAD and origin. All 1,912 recorded source SHA-256 values were checked before execution, and Git status was empty before and after. The runner was invoked without `--force` or autofix. Its exit code 1 reflects measured failed checkpoints; its result is valid JSON, with all 376 outcomes present. The checkpoint definition validator exited 0.

## Mechanical results

Eleven complete batches contain 755 raw outcomes: 548 pass, 164 fail, 43 skip and zero blocked. All batch totals equal both their checkpoint counts and their pass/fail/skip/blocked sums. The aggregate includes the eight generic baseline batches, the separate enterprise-readiness and TYPO3-conformance runs, and the completed security-audit run.

These counts are not a compliance score. Generic absence checks can pass when a framework, language or deployment surface is absent. Failed path/layout assumptions and findings in retrieved originals or archived test fixtures need applicability review. A skipped check did not establish compliance.

The security batch contains 364 pass, seven fail and five skip. Its seven failed checks are:

| Check | Review of applicability | Current correction |
|---|---|---|
| SA-02 | Real repository hygiene gap: `.env` is absent from `.gitignore`. | Root implementation agent notified. |
| SA-14 | Real missing dependency update automation at the baseline. | New `.github/dependabot.yml` enables Composer and Actions updates. |
| SA-15 | Same missing Composer update automation as SA-14. | New Composer ecosystem entry fixes it. |
| SA-DEP-01 | Duplicate dependency update automation gap. | Same new Dependabot configuration. |
| SA-CSP-01 | Inapplicable retrieved-source result: inline script in preserved Alibaba metadata documentation HTML, not a TYPO3-rendered application template. | Preserve the original; it is excluded from the runtime ZIP and current-source SAST. |
| SA-WP-03 | Inapplicable WordPress rule applied to a historical CLI architecture probe that prints a value. This repository is a TYPO3 extension. | Preserve the original evidence. |
| SA-JOOMLA-02 | Inapplicable Joomla rule applied to a historical controlled HTTP test router using a query parameter. | Preserve the original evidence. |

The five security skips concern absent baseline workflows/security reporting files and absent Azure infrastructure. They remain raw skips, not passes.

## Model review decisions

The separate model checkpoint records contain 184 decisions: 65 pass, 28 fail, 57 skip, 24 not applicable, three needs review, three deferred release gates and four definition issues. This is an aggregate of recorded model reviews, not an independent human security review. The four definition issues are not project findings.

## Evidence files

- `baseline-mechanical-aggregate.json`: exact completed mechanical batch totals and consistency checks.
- `baseline-model-review-aggregate.json`: model decisions counted separately by outcome.
- `security-native/security-audit.raw.json`: unmodified completed runner output.
- `security-native/snapshot-hashes.log`: all 1,912 source matches.
- `security-native/snapshot-{head,origin,status-before,status-after}.log`: copied repository binding and unchanged status.
- `security-native/checkpoint-validation.log` and `.exit`: definition validation.

No production source, remote, commit or message to an external party was changed by this review.
