# Independent Reporter and Registry contract review

Reviewer: `/root/fresh_review`. Scope: the three new test/fixture files in `/tmp/http-guard-reporter-contracts`, read-only. Two scoped passes reviewed correctness, security, architecture, readability and performance against the corresponding production contracts. No required finding was identified. This is an independent model review, not a human approval or a new test execution.

The reviewed bytes are:

| File | SHA-256 |
| --- | --- |
| `Tests/HttpGuard/Unit/Policy/DecisionReporterContractTest.php` | `725556352be1df6d71ee11529abd0d2c459f77a2d363d1cf5ca173da84117c3b` |
| `Tests/HttpGuard/Unit/Policy/PolicyRegistryContractTest.php` | `ed9a6bd201bf74aa15ff069a6c94038d4ae6499ed1044c9d86a4fb3b9174b8bf` |
| `Tests/HttpGuard/Unit/Policy/Fixtures/ReporterFunctions.php` | `449dde3f0164971c735828eded3c56e23b489a73461bc60ea86157ba49b6432b` |

The entropy and wipe probes are confined to PHPUnit child processes with global-state preservation disabled. They retain the active test bootstrap and introduce no production autoload changes. Unexpected entropy consumption raises an explicit failure. The wipe probe calls the genuine optional native function and the test separately proves that the caller-owned key remains; neither the test nor its release report claims physical whole-process zeroization. Sampling, rate-window boundaries, failure accounting, canonical host policy and literal key handling have public observable assertions.

Registry cases test genuinely issued contexts, object identity, borrowed tokens, another issuer, inclusive expiry across equal timezone instants and the lifetime of weakly owned context/scope/grant objects. A past `reviewAfter` remains metadata and is not described as an approval gate. The cases do not fabricate contexts through private state tampering.

The author's source-bound execution and mutation evidence was inspected separately from this review. The reported 153-mutant result is explicitly filtered to Reporter/Registry and does not establish the project-wide 90% target. An actual recorded mutant subprocess failure names the new disabled-sampling test after a `<= 0` to `< 0` mutation causes an unexpected entropy draw. Existing killed mutants and new-test witnesses are not conflated. The supplied full Unit results remain the author's executions; this review did not rerun them or start native resources.
