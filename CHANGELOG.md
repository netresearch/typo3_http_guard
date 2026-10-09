# Changelog

All notable changes to HTTP Guard for TYPO3 are recorded here.

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
