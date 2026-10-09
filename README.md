<!-- SPDX-License-Identifier: GPL-2.0-or-later -->
<!-- SPDX-FileCopyrightText: Netresearch DTT GmbH -->

<p align="center">
  <a href="https://www.netresearch.de/">
    <img src="Resources/Public/Icons/Extension.svg" alt="Netresearch DTT GmbH" width="80" height="80">
  </a>
</p>

<h1 align="center">HTTP Guard for TYPO3</h1>

<p align="center">
  Controlled outbound HTTP for TYPO3.<br>
  Public destinations by default; internal endpoints through explicitly bound clients.
</p>

<p align="center">
  <a href="Documentation/Installation/Index.rst"><img src="https://img.shields.io/badge/status-0.1.0%20alpha-orange.svg" alt="Status: 0.1.0 alpha"></a>
  <a href="Documentation/Installation/Index.rst"><img src="https://img.shields.io/badge/TYPO3-13%20%7C%2014-orange.svg?logo=typo3" alt="Supported TYPO3 majors: 13 and 14"></a>
  <a href="Documentation/Development/Index.rst"><img src="https://img.shields.io/badge/PHP-%5E8.2-blue.svg?logo=php" alt="PHP constraint: ^8.2"></a>
  <a href="LICENSE.txt"><img src="https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg" alt="License: GPL-2.0-or-later"></a>
</p>

Developed by [Netresearch DTT GmbH](https://www.netresearch.de/).

## Overview

**HTTP Guard** checks outbound requests before a connection starts and pins the
connection to the verified destination IP addresses. It protects the registered
TYPO3 RequestFactory path and the clients created by its own factories.

Public requests cannot reach private, loopback, metadata or special-purpose
networks. Trusted integrations use endpoint profiles bound to their clients;
a host allowlist or a user-supplied endpoint ID does not grant internal access.

The extension key is `nr_http_guard` and its Composer package name is
`netresearch/nr-http-guard`. The security core, policy data and manual are included
in this one extension.

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

The final reviewed source passes five complete Unit/native executions on PHP
8.5.10: **201 tests and 2,405 assertions each**, covering four distinct SDK
version tuples including both supported SDK minima. The verification report
records the actual versions, source hashes and separate Core process results.

The host also needs `ext-curl`, `curl_multi_exec`, a supported libcurl and access
to controlled DNS servers or configured static host entries. Process proxy
variables are not supported. See [the installation requirements](Documentation/Installation/Index.rst)
for the complete runtime contract.

## Installation

The source is published on GitHub. Version 0.1.0 is an alpha and has not been
published to Packagist or the TYPO3 Extension Repository.

### Classic TYPO3

Build an importable extension ZIP from this checkout:

```bash
python3 Build/Scripts/build-extension.py --output /tmp/nr_http_guard_0.1.0.zip
```

Import the ZIP through the TYPO3 Extension Manager and activate the extension.
The supported official TYPO3 archive supplies the third-party runtime
dependencies. Keep the extension's `composer.json`: TYPO3 uses its class-loading
metadata in classic mode too.

### Composer projects

Use the source directory as a Composer path repository and require
`netresearch/nr-http-guard:0.1.0`. The
[Composer installation guide](Documentation/Installation/Index.rst) contains
the complete project configuration and dependency checks.

## Getting started

Without custom configuration, HTTP Guard uses `enforce` mode. Activation grants
no internal endpoint permissions.

After installing in a Composer project, validate the configuration and runtime:

```bash
vendor/bin/typo3 cache:flush
vendor/bin/typo3 http-guard:config-check
vendor/bin/typo3 http-guard:doctor
```

Classic installations use their Core CLI entry point instead. These diagnostic
commands send no destination HTTP request. Follow the
[rollout guide](Documentation/Operations/Index.rst) and test both an approved
destination and a rejected internal destination through your application's
actual client path.

For internal integrations, configure an endpoint profile and inject a client
bound to that profile through trusted service wiring. For untrusted public URLs,
use Public Fetch and validate the raw URL before creating a PSR-7 URI object.
The [API guide](Documentation/Api/Index.rst) provides complete examples.

## Documentation

The canonical manual lives in [Documentation](Documentation/Index.rst) and is
included in the extension ZIP.

English is the primary language. A [German translation](Documentation/Localization.de_DE/Index.rst)
is also included.

- [Installation and supported versions](Documentation/Installation/Index.rst)
- [Policy configuration and endpoint profiles](Documentation/Configuration/Index.rst)
- [Client APIs and raw URL handling](Documentation/Api/Index.rst)
- [Diagnostics, operations and rollback](Documentation/Operations/Index.rst)
- [Security boundaries and limitations](Documentation/Security/Index.rst)
- [Development and verification](Documentation/Development/Index.rst)
- [Architecture decisions](Documentation/Decisions/Index.rst)

## Security and release status

This is an alpha for evaluation. An independent human security review and a
pilot on an actual operator instance remain outstanding. Passing the technical
test suite does not complete those acceptance steps. See the
[readiness assessment](Documentation/Development/Assessment.rst) for the recorded
evidence and remaining gates.

The guard covers the documented registered TYPO3 path and factory-created
clients. Direct cURL calls, custom sockets, early bootstrap requests and unrelated
SDK clients require separate integration. The optional nr-vault migration is
provided under [integrations/nr-vault](integrations/nr-vault/EVIDENCE.md); the
extension does not automatically protect Vault. Policy snapshots are immutable,
so changes require rebuilding clients and restarting long-lived workers.

Report vulnerabilities privately through
[GitHub's vulnerability reporting form](https://github.com/netresearch/typo3_http_guard/security/advisories/new).

## Development and contributing

The TYPO3 adapter is under `Classes/`; its embedded security core is under
`Classes/HttpGuard/`. Both namespaces belong to this one extension. Unit tests,
controlled wire fixtures and reproduction runners are documented in the
[development guide](Documentation/Development/Index.rst).

Execution evidence and the original requirement/test mapping are in
[verification](verification/requirements-and-tests.md). Historical specifications
and recorded evidence retain their original content; they are not runtime
dependencies.

Use [GitHub issues](https://github.com/netresearch/typo3_http_guard/issues) for bugs
and feature requests. Changes to supported versions, transport options or policy
permissions require the relevant source review and test evidence.

## License

The extension is licensed under [GPL-2.0-or-later](LICENSE.txt). The embedded
security core retains its [MIT license](LICENSE-HttpGuard.txt) and copyright
notices. See [LICENSES.md](LICENSES.md) for the package's license mapping.

Copyright (c) 2026 [Netresearch DTT GmbH](https://www.netresearch.de/).
