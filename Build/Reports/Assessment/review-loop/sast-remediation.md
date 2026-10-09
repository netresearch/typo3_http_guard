# SAST fixture findings and scope

The thirteen recorded findings are resolved through ten source-reviewed exact-rule annotations and three exact private-key paths excluded only after the synthetic-fixture integrity gate passes. No directory, filename-extension, production source or scanner-rule exclusion is introduced for these findings.

The annotations cover fixed qualified-release HTTPS downloads with SHA-256 validation, filtered tar extraction, committed PHP child-process argument arrays, regex-validated filenames inside an owned 0700 directory, and teardown of that owned directory. Four PHP ASTs compare equal before and after these annotations. AST printing also removes one semantically irrelevant trailing argument comma. The comment-stage Python AST comparison remains historical evidence; the separate subsequent classic activation change modifies that fixture's semantics.

The key gate validates the original exact bytes of three keys and three certificates, synthetic identities, matching public keys, CA constraints and certificate chains. It rejects all ten recorded mutations without displaying failed values, including changed keys/certificates, extra `.key`/`.pem` files, a symlink and a missing certificate. The keys and certificates themselves are unchanged. New keys outside the three exact excluded paths remain scanner targets, and extra keys in the fixture directory fail the guard.

The official CI Opengrep v1.19.0 binary is verified against SHA-256 `1d69a41beb88e8e7917f26cc6a16c1edf298f31402807e6d1afbb5d8684c3590`. A pre-annotation control reproduces all thirteen findings and exits 1. The remediation scan runs 357 rules on 416 files, includes the guard script, reports zero findings and exits 0. The repository records only clean metadata and proof summaries, not matched key content or huge raw reports.

## Parsing limits and baseline comparison

This scanner run has 57 parsing limitations: 53 unsupported `readonly` declaration diagnostics, two scalar type/cast diagnostics, one intersection-type diagnostic, and one attempt to parse the non-PHP source patch. These produce 56 `PartialParsing` records and one `Syntax error` record. Every affected path and diagnostic location is retained in `sast-parser-limitations.json`.

For comparison, those same 57 paths were retrieved byte-for-byte with Git archive from original PR head `83f8105ec50d766b31d860ca979464de3c146c22` and scanned with the same pinned binary and live auto rules. That immutable-source control also produces exactly 57 errors. The normalized path, scanner-error type and unsupported-token sets are equal, with no baseline-only or current-only error. Line shifts caused by comments and independent typing fixes are not treated as different parser coverage. The comparison is recorded in `sast-parser-baseline-comparison.json`.

Zero reported findings and a successful finding gate do not establish exhaustive PHP SAST coverage. The existing parser limitations remain explicit; none was hidden or suppressed to obtain the passing exit. PHP syntax checks, static type analysis, native transport tests and independent security review have separate scopes.

Clean execution summaries are in `sast-remediation.json`, `sast-php-semantic-ast.json`, `synthetic-fixture-integrity.json` and `synthetic-fixture-mutations.json`. Source positions and hashes in the AST proof bind the annotation stage. The root task owns the security workflow, later source changes and overall qualification verdict.

## First classic lifecycle follow-up

After the separately authorized native activation implementation, that stage's `Build/Fixtures/initialize-classic.php` and `Build/Fixtures/prepare-classic.py` were scanned again with the same official Opengrep v1.19.0 binary. Both files were scanned, with zero findings, zero parser errors and exit 0. Historical source hashes and the compact result are recorded in `sast-classic-lifecycle-follow-up.json`.

Independent source review found no actionable defect in the new helper or its invocation. The Python subprocess supplies a literal PHP argument array. The helper resolves and checks the local fixture, uses the actual TYPO3 bootstrap and package manager, activates the extension, generates native metadata-based class loading and verifies both extension namespaces. This two-file follow-up does not expand the whole-repository parser coverage described above.

## Final combined source scan

The final reviewed source snapshot, including the suffix-boundary correction, was scanned again using the exact committed Security workflow configuration and the same verified Opengrep v1.19.0 binary. It runs 357 rules on 473 files, reports zero findings and exits 0. The scan includes the synthetic-key guard, Composer-qualification guard, installed-runtime guard, revised inactive-package classic activation helper and classic preparation script. Their tested source hashes are bound in `final-static-summary.json`. The source base is `ab230a3f33b20aedce264dce8af32a2d5be14a73`; the tested working tree additionally contains the uncommitted guard remediation, rather than claiming that remediation is already in the base commit. The guard hash is SHA-256 `f1e3af549096a02aa046718b4ea6d8b6e6a53dd33d5b5a1bc6a980b1325933dc`. This binding also supersedes the first lifecycle helper hash: the reviewed helper is SHA-256 `3109bb2cf5f63ec3684129457af61b98c6b1a6d39af36d9a3dfaf80776bdfd2d` and checks initial inactivity plus the real persisted PackageStates installation path.

The effective key-exclusion boundary is independently verified in `synthetic-exclusion-scope.md` and its compact JSON proof. The refreshed preflight rejects additional matching suffix files, directories and symlinks anywhere in the source tree, plus symlinked fixed-directory ancestors. All 19 negative mutations fail without value disclosure; two positive controls pass. The workflow requires this guard before applying its existing three CLI exclusions. An unrelated key path remains a scanner input.

The final run retains exactly the same 57 parser-limitation path, scanner-type and initial-token signatures: 56 `PartialParsing` records and one `Syntax error`. Current diagnostic locations are retained in `final-sast-parser-limitations.json`; no new signature appeared. Categories describe each record's initial unsupported token, while the locations preserve the scanner's additional spans. This successful finding gate still does not establish exhaustive PHP SAST.

All six workflow files pass actionlint 1.7.12. Offline strict-collection zizmor 1.30.0 reports zero findings. YAML contract checks confirm that CI and native Verification no longer override PHPStan/PHPUnit development constraints, both exact Core graphs are audited, requested runtime constraints are checked before matrix overrides, the resolved graph is checked afterward, and fixture integrity precedes SAST. These are local results; remote checks are separate.
