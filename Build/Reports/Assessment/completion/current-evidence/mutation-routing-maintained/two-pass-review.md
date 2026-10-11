# Independent review by ci_finalize

Two fresh read-only passes covered correctness, readability, architecture, security and performance for the exact released timeout and whole-file workflow proposals. These are two passes by one independent reviewer, not two different reviewers or claims that native mutation testing has completed.

## Timeout calibration

Root configuration SHA-256 `5e8d64335b6d59df360d5bff4652605e80eec4c6361744e2be6eb1585519f279` and proposal `a33ab444c6aa17df4b624e8da9c69cfa89f8951932600edef7aa0cff3aa19ada` differ solely in `timeout: 10` becoming `timeout: 20`. Structured comparison after removing that key is equal. All Classes, default mutators, both 90 thresholds, the serial runner, bootstrap and reports remain unchanged.

Pass 1 recomputed the author's timing proof from its preserved original JUnit and CIDR coverage inputs. The actual baseline has 1,584 tests, 8,329 assertions, no failures/errors/skips and 17.844510 seconds total. The 68 unique class suites sum to 17.844511 seconds. The 23-case CIDR portability class itself took 0.369953 seconds. Seven covered CIDR lines reference 42 unique classes whose full-suite times sum to 11.084747 seconds. There are no missing class timings; those seven lines exceed 10 and none exceed 20.

Installed Infection source was read in the first pass: its nominal-time calculator deduplicates test classes and its mutation runner skips covered mutants at or above the configured nominal duration before execution. The nominal bound can therefore exceed the covering cases' actual duration. Pass 2 repeated config equality, original XML hashes, exact timing reconstruction and the complete timing chain. The previous temporary vendor locations disappeared during review. To preserve primary-source reproducibility, the reviewer retrieved unchanged official Infection 0.35.6 source copies; all five calculator/runner/scope source hashes exactly match the earlier installed-source receipts. This retrieval is not a fresh native run or a vendor installation. The additional JUnit source confirms class-level duration assignment.

Readability and architecture: the one-key calibration retains the canonical native configuration. Security: it changes no production HTTP/DNS/policy timeout and introduces no exclusion or score override. Performance: each actually timed-out mutant can wait at most ten seconds longer; the CI job still has its separate 60-minute bound. Future slower baselines require their own timing checks.

No required finding remains. Completion still requires an actual source-bound all-Classes run with zero skipped/ignored mutants. The `--with-timeouts` argument keeps actual timeouts as escaped outcomes; they are not test kills. Error and syntax outcomes must also remain separate.

Primary source: [JUnit assignment](https://raw.githubusercontent.com/infection/infection/0.35.6/src/TestFramework/Coverage/JUnit/JUnitTestExecutionInfoAdder.php), [nominal timing](https://raw.githubusercontent.com/infection/infection/0.35.6/src/TestFramework/Tracing/TestTotalTimeCalculator.php), [skip decision](https://raw.githubusercontent.com/infection/infection/0.35.6/src/Process/Runner/MutationTestingRunner.php).

## Whole changed-file mutation scope

Root workflow SHA-256 `287a0852a350664bc62a2b76417f053e93b52370ab286e57db69e3e7327e830b` and candidate `2ae7dae8b67f8069ea3542550f960ada6979453decd373f3271c042c06fb069e` retain the same source and score configuration. The released author input manifest passed an independent full hash check before its temporary tool location disappeared; its raw evidence remains preserved. Official 0.35.6 source copies independently reproduce the original installed-source hashes.

Pass 1 traced positional source arguments through the installed classifier/configuration to `PlainFilter` and `NullSourceLineMatcher`. GitDiff filters instead select `GitDiffSourceLineMatcher` and limit mutation to changed lines. Passing exact changed source files after `--` fixes this difference without broadening PR work to every unchanged source file. Non-PR events retain all configured Classes. Documentation/deletion-only PRs explicitly skip measurement without inventing a passing score.

The actual Git diff is checked before array loading, uses NUL-delimited AMR paths and includes direct plus nested Classes PHP. The 40-hex base must resolve to a commit. Selected paths must be regular, non-symlink Classes PHP files. Quoted array expansion retains spaces, commas, embedded newlines and shell syntax as literal arguments. Temporary-list cleanup is limited to the freshly owned file. Git, validation and measurement failures propagate.

Pass 2 reran all eleven author temporary-Git controls successfully, reviewed the tiny actual-tool calibration and rechecked actionlint/zizmor/Bash syntax. The actual calibration preserves comment-only old GitDiff zero mutants versus positional eight test kills, and single-line old four versus candidate eight with four unchanged-function test kills. The five effective repeat inputs are unchanged. These fixture scores establish selection behavior, not project/native/global MSI. The released author shell file contains one extra trailing blank line beyond the YAML scalar; this is behaviorally inert. The maintained controls' extracted block exactly matches a proper yq/jq parse of the YAML scalar byte for byte.

Readability and architecture: one bounded Git lookup and one array feed the existing native command; no second configuration or new dependency appears. Security: no event input is evaluated as shell source and explicit argument boundaries avoid path splitting. Performance: work is bounded by the changed file list; stronger whole-file qualification intentionally mutates unchanged lines inside those selected files.

No required finding remains in either pass. The applicable fixed threshold and all-source qualification must be measured separately after Root integration.

## Maintained routing control proposal

`source/Tests/Build/test_mutation_routing.py` is a proposed new file, not written to Root. It exercises the actual workflow literal shell block, not a copied implementation. It uses Python's standard library, Git and Bash already used by CI; no Infection, parser or AST tool is required. Git fixtures use immutable PolicyException byte copies with mode-only modifications, so no PHP source is rewritten or executed. The tightly bounded literal-block extraction refuses a renamed/ambiguous/non-literal step; full YAML validity remains actionlint's job.

Twelve actual tests passed twice (0.674 seconds and 0.848 seconds), including deletion-only skip, direct/nested modification, renamed-plus-modified file, five unusual added paths, symlink rejection, invalid/missing base, Git failure, measurement failure and full-scope non-PR events. Every run verifies cleanup of its owned temporary path list. Replacing the candidate with the old Root workflow causes eight failures. Removing NUL output causes five failures. The exact candidate was restored and rehashed after both intentional negative experiments. These controls verify argv, routing, failure propagation and ownership; the fixed recorder measures no MSI.

The proposal matches CI's existing `unittest discover -s Tests/Build -p 'test_*.py'` discovery. No Root, vendor, Docker, native targets or repository refs were modified by this review. Its parent must separately review the newly authored maintained test before integrating it.
