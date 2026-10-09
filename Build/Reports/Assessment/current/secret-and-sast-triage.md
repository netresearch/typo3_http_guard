# Live secret scanning and SAST triage

This is the preserved first-run assessment record. Subsequent fixes and precise
scanner policies are recorded in [the review loop](../review-loop/); the cookie
remediation is [documented separately](../review-loop/cookie-remediation.md).

The first completed Security run scanned seven Git commits and returned **376 Betterleaks findings**: 373 generic matches and three private keys. Every original Git blob, matched source line and fingerprint was reviewed locally without displaying the matched material.

- 348 findings are 64-hex source/artifact SHA256 metadata or explicitly tagged Docker/install digests. JSON values and their source/checksum contexts were parsed and verified individually.
- 24 findings are public 40-hex OpenPGP signing-key fingerprints in recorded Docker image metadata. They are public verification identifiers, not private signing material.
- Three keys are isolated synthetic TLS fixtures. Locally derived public material exactly matches the corresponding certificates issued by `HTTP Guard SYNTHETIC TEST CA`. These fixtures are excluded from the installable extension ZIP and confer no default production trust.
- One finding is an anonymous public-document response cookie named `cr_token`, returned by Alibaba Cloud. Its captured header gives no expiry or maximum age. Its external validity is unestablished; it remains visible for review. It must not be described as an expired trace ID.

[.betterleaksignore](../../../../.betterleaksignore) contains only the **375 individually verified immutable commit/path/rule/line fingerprints**. There are no path-wide, commit-wide, rule-wide or hexadecimal-pattern exclusions. Future findings still fail the scanner. A fresh local Betterleaks 1.1.2 history run confirms exactly one remaining finding, the historical response cookie, and exits 2. The Git history and downloaded originals are unchanged.

The completed Opengrep run reports **13 findings**. [The applicability report](opengrep-applicability.json) preserves every rule, source location and rationale. All concern controlled fixtures or tooling:

- The classic fixture URL derives only from two hardcoded qualified release versions and the fixed HTTPS `get.typo3.org` origin; no caller controls its scheme or hostname. The archive SHA256 is verified before extraction. The extraction call already supplies `filter='data'`; the rule overlooks that protection.
- Child processes use argument arrays with `PHP_BINARY` and committed fixture scripts for real capability, DNS and HTTP tests. They do not evaluate shell strings or request-selected executables.
- The three private keys are the verified synthetic fixtures described above.
- The optional Vault test router accepts only an anchored lowercase alphanumeric/hyphen case identifier before building filenames in a random temporary directory created with mode 0700 by its parent process. Slashes, dots and traversal syntax cannot pass that validation. Teardown deletes only that owned directory's fixture files.

No production finding was established by these 13 rules. No SAST rule or path was suppressed, and the scan remains red until the findings are explicitly reviewed. Genuine G8 static-analysis errors, three upstream dependency advisories, the cookie review, independent human G6 and the operator pilot remain separate open gates. This agent triage does not substitute for human security approval.
