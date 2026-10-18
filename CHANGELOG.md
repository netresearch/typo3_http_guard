# Changelog

All notable changes to HTTP Guard for TYPO3 are recorded here.

## [0.1.4] - 2026-10-18

Alpha maintenance release with documentation updates and expanded regression coverage.

### Added

- Add 102 unit test cases covering configuration and policy boundaries, DNS validation and resolver fallback, TYPO3 registration, runtime checks and the transport lifecycle.
- Verify complete DNS-over-TCP responses when length-prefix and payload fragments become readable separately, with a deterministic readiness control.
- Add `.bestpractices.json` with source-backed OpenSSF proposals and explanations for individual criteria.

### Changed

- Make the project test runner executable for direct invocation.
- Align both manual languages with Netresearch cards, teal accents and footer branding; update links to the published manuals while preserving dated publication observations.
- Document four static-analysis false positives at the reviewed TYPO3 initialization calls in development and test fixtures.

HTTP protection behavior and dependency constraints are unchanged. The extension remains an alpha for evaluation.

## [0.1.3] - 2026-10-11

Alpha patch release aligning the installable package contents across delivery routes.

### Fixed

- Exclude the two documentation editor settings from canonical and Git source archives while retaining them in the documentation source, matching TER's package boundary.
- Read only their explicit development-file declarations from an immutable source when verifying that release; retain historical package contents.

## [0.1.2] - 2026-10-11

Alpha maintenance release with stricter input contracts and executable development checks.

### Fixed

- Reject bracketed IPv4/DNS authorities and malformed DNS address records before creating a connection plan.
- Validate configuration, policy data, request options and registration shapes with controlled fail-closed errors.
- Settle and clean up leases when runtime capabilities change or a transfer no longer has its request.
- Keep decision reporting bounded and redacted and preserve request/client/policy isolation.
- Map native coverage reports to measured repository sources and verify release provenance with the supported GitHub CLI options.

### Changed

- Permit Symfony Console 8 alongside 7.4 in compatible dependency graphs.
- Apply the shared TYPO3 extension coding style and separate MIT/GPL source scopes.
- Add executable style, level 10 analysis, architecture, fuzz, policy-performance and whole-file mutation checks, with maintained controls for build and security tooling.
- Keep the active manual, requirements and accepted decisions under Documentation, tests under Tests and support tools under Build. Preserve original specifications, obsolete runs and the nr-vault reference overlay in immutable Git history.
- Restore all fourteen original numbered ADRs in English and German, retaining their rationale, alternatives, consequences and evidence IDs. Record the accepted single-extension, public RequestFactory, controlled DNS wire and single-use cURL decisions as ADR-0015 through ADR-0018, with explicit relationships to the historical proposals.
- Include an English manual and its German localization in the single extension package. Default protection still applies to the registered TYPO3 RequestFactory path; Vault and unrelated clients require explicit integration.

This entry describes source changes. It does not assert a completed human security review, operator pilot, enterprise certification, OpenSSF badge level or hosted-manual publication. Recorded verification results retain their own sources and scopes.

## [0.1.1] - 2026-10-09

Alpha release with the same HTTP protection as 0.1.0.

### Fixed

- Run the shared DCO check only for pull requests or merge groups. The CI gate requires its success there and permits only that check to be skipped on main pushes or manual runs; PHP, documentation and workflow lint must still succeed. The original 0.1.0 tag remains unchanged. Its release was blocked before artifact and TER publication because the PR-only DCO workflow was invoked on main.

## [0.1.0] - 2026-10-09

Initial alpha for evaluation, distributed as one complete TYPO3 extension.

### Added

- One installable TYPO3 extension containing the HTTP policy kernel, controlled transport, typed clients and offline diagnostics.
- Automatic default SSRF protection for existing calls through TYPO3's registered RequestFactory, blocking private, loopback, cloud metadata and special-purpose destinations, including URLs from users, imports and external payloads.
- Explicit endpoint binding and public fetch behavior, complete DNS/address validation and per-attempt authorization.
- Genuine Composer and classic TYPO3 installation fixtures, isolated wire counters, targeted security mutations and a qualification ledger.
- An English extension manual with a separate German localization.
- Repository verification and security workflows, dependency update configuration and contributor/security guidance.
- Floating native CI checks for the latest compatible Core 13/14 and Guzzle 7/8 graphs, including weekly scheduled runs.
- Publication through the maintained Netresearch release and TER workflows, with matching package content, signed artifacts and provenance; documentation registration uses the official TYPO3 webhook.

### Changed

- Packaging combines the original kernel and TYPO3 integration while retaining both public PHP namespaces and the kernel's MIT attribution.
- Production dependencies use semantic ranges with security/API minima: Core `^13.4.36 || ^14.3.8`, PHP `^8.2`, Guzzle `^7.15.2 || ^8.2`, Promises `^2.5.1 || ^3.0.2`, and PSR-7 `^2.13.0 || ^3.1`. Compatible patches and minors require no new extension release.
- Runtime checks validate the actual Core parent API and SDK capabilities. Each lease uses a public single-use cURL factory to prevent hidden native retries from bypassing authorization.

### Production qualification

Independent human security review and a representative operator pilot remain outstanding for production acceptance. This alpha publication does not establish enterprise certification or SLSA level 3. The release workflow verifies artifacts and publication separately; registering the documentation webhook does not by itself confirm that an online manual has been approved and rendered.

[0.1.4]: https://github.com/netresearch/typo3_http_guard/compare/v0.1.3...v0.1.4
[0.1.3]: https://github.com/netresearch/typo3_http_guard/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/netresearch/typo3_http_guard/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/netresearch/typo3_http_guard/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/netresearch/typo3_http_guard/tree/v0.1.0
