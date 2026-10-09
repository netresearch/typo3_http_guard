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

## Follow-up classic lifecycle scan

After the separately authorized native activation implementation, the current `Build/Fixtures/initialize-classic.php` and `Build/Fixtures/prepare-classic.py` were scanned again with the same official Opengrep v1.19.0 binary. Both files were scanned, with zero findings, zero parser errors and exit 0. Source hashes and the compact result are recorded in `sast-classic-lifecycle-follow-up.json`.

Independent source review found no actionable defect in the new helper or its invocation. The Python subprocess supplies a literal PHP argument array. The helper resolves and checks the local fixture, uses the actual TYPO3 bootstrap and package manager, activates the extension, generates native metadata-based class loading and verifies both extension namespaces. This two-file follow-up does not expand the whole-repository parser coverage described above.
