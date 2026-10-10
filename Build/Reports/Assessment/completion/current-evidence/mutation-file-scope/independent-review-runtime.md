# Independent PM-41 review

Reviewer: runtime_assessment_fixes, independent of the workflow author. Two read-only passes of the same released delta found no required findings. These are separate review passes, not claims of two different reviewers or fresh CI/native execution.

The guarded workflow changes from SHA-256 `287a0852a350664bc62a2b76417f053e93b52370ab286e57db69e3e7327e830b` to `2ae7dae8b67f8069ea3542550f960ada6979453decd373f3271c042c06fb069e`. The extracted Bash step is `04c76bfef0730f5b3b4ce4a95fb7afca800a761eaa06ae999f81a09b3c6307fd`. All entries of the author's `release-inputs.sha256` were independently rehashed successfully.

## Pass 1: behavior and five axes

Correctness: the selected AMR production paths use NUL-delimited Git output and separate positional arguments, so the installed Infection 0.35.6 uses `PlainFilter` and `NullSourceLineMatcher`. Reading its `Container`, `SourceFilterOptions`, `PathsArgument`, `PositionalPathsClassifier`, `ConfigurationFactory` and `BasicSourceCollector` confirms the route; positional file names do not enter the deprecated comma-splitting `--filter` parser. A PR without selected production files explicitly skips measurement without reporting a score. Non-PR events keep the configured full source set. Git and Infection failures propagate.

Readability and architecture: the small delta preserves the existing native job and canonical configuration. The array has one role, and the temporary file makes Git failure observable before `mapfile` reads results. No second mutation configuration, dependency or source exclusion is introduced.

Security: the base must be a 40-character hexadecimal commit and an existing Git commit. Selected paths must be regular, non-symlink files under `Classes/`. Quoted array expansion after `--` preserves spaces, commas, internal newlines and shell characters as literal arguments. The owned temporary list is removed by its EXIT trap. No expression is evaluated as shell source.

Performance: the additional work is one bounded Git diff and file validation. Whole changed files intentionally provide stronger coverage than changed lines; scheduled/manual full qualification retains its denominator and serial runner.

## Pass 2: regression evidence and scope

Reviewed all eleven actual temporary-Git controls, their passing raw log, YAML re-extraction, Bash syntax, actionlint and zizmor receipts. The controls meaningfully exercise direct/nested paths, rename plus edit, unusual literal names, absent/invalid base, Git failure, symlink rejection, non-PR full scope and measurement failure. Their recording tool checks argument routing only; it is not an MSI test.

The separate real-tool calibration retains the original zero-mutant comment-only GitDiff result, then generates and test-kills eight mutants through positional files. For a single changed line, the old route generates four while the exact proposed step generates eight, including four test-killed mutants in the unchanged function. The final repeat preserves all five fixture inputs and kills all eight. These are tiny deterministic local PHPUnit/Infection controls, not a project/native/global score. The fixed 90/90 thresholds, default mutators, uncovered cases, timeouts and configured all-Classes source set are preserved.

The installed primary source overrides the older skill reference's claim that `--git-diff-filter` alone performs whole-file selection. No frozen assessment definition is rewritten. Existing zizmor offline warnings remain qualified; no online advisory audit is claimed.

Verdict: release is ready for the parent's guarded integration. A later project-wide result must retain its own source and denominator binding.
