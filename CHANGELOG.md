# Changelog

All notable changes will be recorded here. No release has been published yet.

## [Unreleased]

### Added

- One installable TYPO3 extension containing the HTTP policy kernel, controlled transport, typed clients and offline diagnostics.
- Explicit endpoint binding and public fetch behavior, complete DNS/address validation and per-attempt authorization.
- Genuine Composer and classic TYPO3 installation fixtures, isolated wire counters, targeted security mutations and a qualification ledger.
- An English extension manual with a separate German localization.
- Repository verification and security workflows, dependency update configuration and contributor/security guidance.
- Floating native CI checks for the latest compatible Core 13/14 and Guzzle 7/8 graphs, including weekly scheduled runs.

### Changed

- Packaging combines the original kernel and TYPO3 integration while retaining both public PHP namespaces and the kernel's MIT attribution.
- Production dependencies use semantic ranges with security/API minima: Core `^13.4.36 || ^14.3.8`, PHP `^8.2`, Guzzle `^7.15.2 || ^8.2`, Promises `^2.5.1 || ^3.0.2`, and PSR-7 `^2.13.0 || ^3.1`. Compatible patches and minors require no new extension release.
- Runtime checks validate the actual Core parent API and SDK capabilities. Each lease uses a public single-use cURL factory to prevent hidden native retries from bypassing authorization.

### Release gates

Independent human security review, an operator pilot, signed release artifacts, reviewed SBOM/provenance and explicit registry publication remain pending. The local alpha version is 0.1.0; this is not a published release tag.
