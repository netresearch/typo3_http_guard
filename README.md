<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->

<p align="center">
  <a href="https://www.netresearch.de/">
    <img src="Resources/Public/Icons/Extension.svg" alt="Netresearch DTT GmbH" width="80" height="80">
  </a>
</p>

<h1 align="center">HTTP Guard for TYPO3</h1>

<p align="center">
  Default SSRF protection for TYPO3's HTTP client.<br>
  Install, activate and protect existing RequestFactory calls.
</p>

<!-- Row 1: CI and coverage -->
<p align="center">
  <a href="https://github.com/netresearch/typo3_http_guard/actions/workflows/ci.yml"><img src="https://github.com/netresearch/typo3_http_guard/actions/workflows/ci.yml/badge.svg?branch=main" alt="CI status on main"></a>
  <a href="https://app.codecov.io/gh/netresearch/typo3_http_guard"><img src="https://codecov.io/gh/netresearch/typo3_http_guard/graph/badge.svg" alt="Codecov service status"></a>
</p>

<!-- Row 2: Verified security report and release provenance -->
<p align="center">
  <a href="https://securityscorecards.dev/viewer/?uri=github.com/netresearch/typo3_http_guard"><img src="https://api.securityscorecards.dev/projects/github.com/netresearch/typo3_http_guard/badge" alt="OpenSSF Scorecard report"></a>
  <a href="https://github.com/netresearch/typo3_http_guard/actions/runs/37964411091"><img src="https://img.shields.io/badge/provenance-v0.1.1%20verified-blue.svg" alt="v0.1.1 archive provenance and signature verification"></a>
</p>

<!-- Row 3: Supported ranges, license and release state -->
<p align="center">
  <a href="Documentation/Installation/Index.rst"><img src="https://img.shields.io/badge/status-0.1.1%20alpha-orange.svg" alt="Status: 0.1.1 alpha"></a>
  <a href="Documentation/Development/Index.rst"><img src="https://img.shields.io/badge/PHP-%5E8.2-blue.svg?logo=php" alt="PHP constraint: ^8.2"></a>
  <a href="Documentation/Installation/Index.rst"><img src="https://img.shields.io/badge/TYPO3-13%20%7C%2014-orange.svg?logo=typo3" alt="Supported TYPO3 majors: 13 and 14"></a>
  <a href="LICENSE.txt"><img src="https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg" alt="License: GPL-2.0-or-later"></a>
</p>

<!-- Row 4: Published TYPO3 Extension Repository metadata -->
<p align="center">
  <a href="https://packagist.org/packages/netresearch/nr-http-guard"><img src="https://typo3-badges.dev/badge/nr_http_guard/composer/shields.svg" alt="Composer package"></a>
  <a href="https://extensions.typo3.org/extension/nr_http_guard"><img src="https://typo3-badges.dev/badge/nr_http_guard/downloads/shields.svg" alt="TER downloads"></a>
  <a href="https://extensions.typo3.org/extension/nr_http_guard"><img src="https://typo3-badges.dev/badge/nr_http_guard/extension/shields.svg" alt="Extension key"></a>
  <a href="https://extensions.typo3.org/extension/nr_http_guard"><img src="https://typo3-badges.dev/badge/nr_http_guard/stability/shields.svg" alt="TER stability"></a>
  <a href="https://extensions.typo3.org/extension/nr_http_guard"><img src="https://typo3-badges.dev/badge/nr_http_guard/typo3/shields.svg" alt="TER TYPO3 majors"></a>
  <a href="https://extensions.typo3.org/extension/nr_http_guard"><img src="https://typo3-badges.dev/badge/nr_http_guard/version/shields.svg" alt="Published TER version"></a>
</p>

Developed by [Netresearch DTT GmbH](https://www.netresearch.de/).

## Overview

Install and activate **HTTP Guard** to add server-side request forgery (SSRF)
protection to TYPO3's standard HTTP client
(`TYPO3\CMS\Core\Http\RequestFactory`). Existing calls through the registered
factory are protected automatically. No application rewrite or custom policy
configuration is needed to enable the default protection.

When your application fetches URLs from user input, imports or external
payloads, the default `enforce` mode blocks private, loopback, cloud metadata
and special-purpose destinations before a connection starts. DNS results and
redirects are checked too; allowed connections are pinned to verified IP
addresses.

Internal integrations need endpoint profiles and clients explicitly bound to
those profiles. Adding a profile does not grant ordinary RequestFactory calls
internal access; a user-supplied endpoint ID does not grant that permission.

The extension key is `nr_http_guard` and its Composer package name is
`netresearch/nr-http-guard`. The security core, policy data and manual are included
in this one extension.

## Installation

### Composer projects

Install the published 0.1 series from
[Packagist](https://packagist.org/packages/netresearch/nr-http-guard):

```bash
composer require netresearch/nr-http-guard:^0.1
```

Composer registers the extension automatically. Version 0.1.1 is an alpha for
evaluation; see [the installation guide](Documentation/Installation/Index.rst)
for the supported Core and transport requirements.

### Classic TYPO3

Install `nr_http_guard` from the
[TYPO3 Extension Repository](https://extensions.typo3.org/extension/nr_http_guard).
Activate the extension in the Extension Manager and rebuild the system and
dependency injection caches.

For a manual installation from [GitHub releases](https://github.com/netresearch/typo3_http_guard/releases),
extract the source archive and copy the contents of its `nr-http-guard/`
directory into `typo3conf/ext/nr_http_guard/`, then activate and rebuild caches.
Use the TER package for the Extension Manager's ZIP import.

The supported official TYPO3 archive supplies the third-party runtime
dependencies. Keep the extension's `composer.json`: TYPO3 uses its class-loading
metadata in classic mode too. No separate HTTP Guard library is needed.

## Getting started

Default SSRF protection is active after installation and activation. No endpoint
configuration is required for ordinary public HTTP requests.

Validate the installation and runtime in a Composer project:

```bash
vendor/bin/typo3 cache:flush
vendor/bin/typo3 http-guard:config-check
vendor/bin/typo3 http-guard:doctor
```

Classic installations use their Core CLI entry point instead. These diagnostic
commands send no destination HTTP request. Follow the
[rollout guide](Documentation/Operations/Index.rst) and test both a public
destination and a rejected internal destination through your application's
actual client path.

For internal integrations, configure an endpoint profile and inject a client
bound to that profile through trusted service wiring. Public Fetch offers a
dedicated API for untrusted public URLs; validate raw URLs before creating a
PSR-7 URI object. The [API guide](Documentation/Api/Index.rst) provides examples.

## Features

- Strict target normalization and IPv4/IPv6 address classification, including
  mapped IPv6 addresses and metadata denials.
- Controlled DNS resolution with complete CNAME evaluation, address-set checks,
  bounded queries and TCP retry for truncated responses.
- cURL destination pinning and policy checks for redirects, retries and transfer
  startup; unsupported handlers, proxies and transport options fail closed.
- Bound PSR-18 endpoint clients and a credential-free Public Fetch API.
- `enforce`, `observe` and `disabled` modes, configuration validation, offline
  diagnostics and redacted decision events.
- Classic TYPO3 installation from an extension ZIP without a Composer command
  or a separate HTTP Guard library.

## Requirements

The Composer ranges are **TYPO3 `^13.4.36 || ^14.3.8`** and **PHP `^8.2`**.
PHP must also meet the chosen Core's requirements. Compatible updates within
these ranges can install and run without a new HTTP Guard release. The runtime
checks the actual Core parent API, middleware storage and cURL capabilities;
incompatible APIs or unsupported transport paths fail before a native send.

The protected transport supports these dependency ranges:

| Guzzle | Promises | PSR-7 |
|---|---|---|
| `^7.15.2` | `^2.5.1` | `^2.13.0` |
| `^8.2` | `^3.0.2` | `^3.1` |

These minima provide the required transport APIs. Each lease can create only one
native handle, independently of Guzzle's private retry counters. Composer must
resolve a compatible complete graph within the supported majors.

The [verification report](Documentation/Development/Verification.rst) separates
fixed source-bound test runs from the current compatibility checks. Historical
kernel coverage spans PHP 8.2–8.5; that does not claim that every future PHP
version or framework/PHP combination has already been tested. Classic extension
metadata allows TYPO3 13.4.36–14.3.99 and PHP 8.2.0–8.99.99. Its TYPO3 upper bound
is narrower than Composer's caret ranges. The contiguous TER notation cannot
express the separate Core 14 minimum; the runtime rejects Core 14.0–14.2 and
14.3 versions earlier than 14.3.8. Core's own PHP requirements and runtime
capability checks still apply.

The recorded semantic-contract snapshot passes five complete Unit/native
executions on PHP 8.5.10: **201 tests and 2,405 assertions each**, covering four distinct SDK
version tuples including both supported SDK minima. The verification report
records the actual versions, source hashes and separate Core process results.

The host also needs `ext-curl`, `curl_multi_exec`, a supported libcurl and access
to controlled DNS servers or configured static host entries. Process proxy
variables are not supported. See [the installation requirements](Documentation/Installation/Index.rst)
for the complete runtime contract.

## Documentation

The manual is included in [Documentation](Documentation/Index.rst) and the
extension ZIP. The
[hosted English manual](https://docs.typo3.org/p/netresearch/nr-http-guard/main/en-us/)
is awaiting initial publication by TYPO3.

English is the primary language. A [German translation](Documentation/Localization.de_DE/Index.rst)
is also included.

- [Installation and supported versions](Documentation/Installation/Index.rst)
- [Policy configuration and endpoint profiles](Documentation/Configuration/Index.rst)
- [Client APIs and raw URL handling](Documentation/Api/Index.rst)
- [Diagnostics, operations and rollback](Documentation/Operations/Index.rst)
- [Security boundaries and limitations](Documentation/Security/Index.rst)
- [Development and verification](Documentation/Development/Index.rst)
- [Architecture decision records](Documentation/Adr/Index.rst)

## Security and release status

Version 0.1.1 is a published alpha for evaluation. Alpha development and merging
proceed after independent agent review, resolved findings and green applicable
checks; no additional human approval or operator-pilot gate applies at this
stage. Neither a human security review nor a representative operator pilot is
claimed completed. They remain recommendations when assessing production use.
See the [assessment reconciliation](Documentation/Development/Reconciliation.rst)
for every frozen checkpoint, source-bound remediation and external
publication limits. Its measured scopes distinguish full-source Infection
runs from focused contract tests; a passing focused result does not establish
the full 90% mutation targets.

The public Codecov report for main commit `27a958a` is complete: **82.06% line
coverage**, with 2,141 hits among 2,609 measured lines in 63 files. Branch
coverage is not established by that report. The public Scorecard result keeps
its own source binding. OpenSSF Best Practices/Baseline registration remains
an external access gap: the exact public searches return no project ID, and
authenticated registration access is currently unavailable.
Project metadata and evidence-backed answers are prepared; no attained badge
level is claimed.

The [v0.1.1 release](https://github.com/netresearch/typo3_http_guard/releases/tag/v0.1.1)
provides signed archives, supplemental SBOMs and checksums. Its
[verification-only run](https://github.com/netresearch/typo3_http_guard/actions/runs/37964411091)
verified the existing signatures and archive provenance without republishing.
See [release provenance](Documentation/Development/ReleaseProvenance.rst) for
verification commands and scope; this is no SLSA level 3 certification.

The guard covers the documented registered TYPO3 path and factory-created
clients. Direct cURL calls, custom sockets, early bootstrap requests and unrelated
SDK clients require separate integration. The optional nr-vault migration is
documented in the [API guide](Documentation/Api/Index.rst) with an immutable
reference patch; the extension does not automatically protect Vault. Policy snapshots are immutable,
so changes require rebuilding clients and restarting long-lived workers.

Report vulnerabilities privately through
[GitHub's vulnerability reporting form](https://github.com/netresearch/typo3_http_guard/security/advisories/new).

## Contributing

Follow [CONTRIBUTING.md](CONTRIBUTING.md) for development setup, local hooks,
review and signed Conventional Commits with DCO sign-off. Report vulnerabilities
through [SECURITY.md](SECURITY.md), using the private reporting channel.

## Development

Install development tools with `composer install`; they live under `.Build/vendor/`.
CaptainHook installs commit-message and staged-secret/quality checks. Use
`composer check:harness`, `composer check:secrets` and `composer check:local` for
the corresponding repository checks. CI remains the authoritative merge gate.

The integrated offline Unit suite passes **1,535 tests and 7,199 assertions**
on each genuine Core 13/Guzzle 7 and Core 14/Guzzle 8 graph on PHP 8.5.11.
Kernel and actual Core 13/14 level 10 analysis, architecture and Rector pass.
The recorded style dry run proposes no changes in **196 files** across the
separate extension/test/tool and embedded-kernel scopes. These local results
retain their source bindings and are separate from final remote checks.
The complete local quality run and 118 Build-tool controls pass against
850 unchanged working files and 21,601 unchanged installed dependency files.
This freeze precedes generated reports/manuals and checksum guard and baseline
corrections; those later corrections retain separate current validation.

The completed native all-source run passes the unchanged 90%/90% targets:
**90.43% MSI and 91.54% Covered MSI** across 3,794 mutants, with no skipped
or ignored mutants. All 263 measured inputs and 21,601 installed dependency
files remain unchanged; 3,424 mutants are killed by tests. Earlier scores
retain their historical source scopes. The named GitHub policy reference measures
**0.604912 ms p95** against 2 ms on its separately recorded source and runtime.
The per-ID report preserves all 940 original IDs and historical outcomes.
See the [reconciliation](Documentation/Development/Reconciliation.rst).

The TYPO3 adapter is under `Classes/`; its embedded security core is under
`Classes/HttpGuard/`. Both namespaces belong to this one extension. Unit tests,
controlled wire fixtures and reproduction runners are documented in the
[development guide](Documentation/Development/Index.rst).

The manual contains [current verification](Documentation/Development/Verification.rst)
and the [original requirement IDs](Documentation/Development/Requirements.rst).
Maintained source-bound summaries live under `Build/Reports/Assessment/`;
the manual links immutable historical specifications and execution records.

Use [GitHub issues](https://github.com/netresearch/typo3_http_guard/issues) for bugs
and feature requests. Changes to supported versions, transport options or policy
permissions require the relevant source review and test evidence.

## Credits and contributors

Maintained by [Netresearch DTT GmbH](https://www.netresearch.de/).
[GitHub contributors](https://github.com/netresearch/typo3_http_guard/graphs/contributors)
records the actual project contributions. See [CONTRIBUTING.md](CONTRIBUTING.md)
for participation and review.

## License

The extension is licensed under [GPL-2.0-or-later](LICENSE.txt). The embedded
security core retains its [MIT license](LICENSE-HttpGuard.txt) and copyright
notices. See [LICENSES.md](LICENSES.md) for the package's license mapping.

Copyright (c) 2026 [Netresearch DTT GmbH](https://www.netresearch.de/).
