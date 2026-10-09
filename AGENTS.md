<!-- Last Updated: 2026-10-09 | Last verified: 2026-10-09 -->
# HTTP Guard agent guide

## Overview

HTTP Guard is one TYPO3 extension, `nr_http_guard`, with an embedded HTTP security kernel.
Read the nearest scoped instruction and the user's request before changing a file.
Explicit user authorization takes precedence over repository guidance.

## Setup

Use PHP within the range in [composer.json](composer.json), Composer, Git, Python 3 with virtual-environment support and Bash.
Install development dependencies with `composer install`; CaptainHook installs the enabled hooks.
Use an isolated branch. Keep environment credentials outside Git. No database is needed for Unit tests.
Install Python build requirements in an isolated environment as described in [CONTRIBUTING.md](CONTRIBUTING.md).

## Commands

| Task | Command |
| --- | --- |
| Agent guidance and hook/CI consistency | `composer check:harness` |
| Staged secret detection | `composer check:secrets` |
| Local deterministic quality checks | `composer check:local` |
| PHP style, without rewriting | `composer ci:test:php:cgl` |
| Curated static analysis | `composer ci:test:php:phpstan` |
| Offline extension and kernel Unit tests | `composer ci:test:php:unit` |
| Native controlled transport tests | `composer ci:test:php:functional` |
| Deterministic extension archive | `python3 Build/Scripts/build-extension.py` |

```bash
composer check:harness
composer check:local
```

See [development commands](Documentation/Development/Index.rst) and [real Core fixtures](Build/Fixtures/README.md).
Do not claim tests passed from command existence; retain actual results and source bindings.

## Architecture

The adapter supplies TYPO3 configuration, middleware registration and diagnostics.
The embedded kernel has no TYPO3 globals or Vault secrets. It owns policy, DNS and native transport controls.
See [the single-package decision](Documentation/Decisions/SingleExtension.rst) and [security boundaries](Documentation/Security/Index.rst).

## File Map

| Area | Entry point |
| --- | --- |
| TYPO3 integration | [Classes/Http/RawRequestFactoryRegistration.php](Classes/Http/RawRequestFactoryRegistration.php) |
| Kernel policy and transport | [Classes/HttpGuard/](Classes/HttpGuard/) |
| Services and registry | [Configuration/Services.yaml](Configuration/Services.yaml) |
| English manual and German localization | [Documentation/](Documentation/) |
| Source-bound qualification | [verification/README.md](verification/README.md) |
| Scoped PHP instructions | [.github/instructions/php.instructions.md](.github/instructions/php.instructions.md) |
| Scoped test instructions | [.github/instructions/tests.instructions.md](.github/instructions/tests.instructions.md) |

## Golden Samples

| Change | Read first |
| --- | --- |
| Endpoint policy | [Classes/HttpGuard/PolicyEngine.php](Classes/HttpGuard/PolicyEngine.php) |
| Core compatibility | [Classes/Http/RequestFactoryCompatibility.php](Classes/Http/RequestFactoryCompatibility.php) |
| Native I/O | [Classes/HttpGuard/NativeOperation.php](Classes/HttpGuard/NativeOperation.php) |
| Native security regressions | [Tests/HttpGuard/Integration/](Tests/HttpGuard/Integration/) |

## Utilities

Reuse the policy/normalization code and `NativeOperation`; avoid alternative request or DNS paths.
See `Build/Scripts/check-composer-qualification.py` for support-range and fixture checks.
See `Build/Scripts/check-synthetic-fixtures.py` for exact synthetic TLS fixture validation.

## Heuristics

Preserve semantic dependency ranges and supported security floors. A successful solver does not prove native behavior.
Use source comparison and real Core/native qualification for new API shapes, supported majors or transport changes.
Keep English project metadata; extend both manual languages when changing documented behavior.

## Workflow

Read [CONTRIBUTING.md](CONTRIBUTING.md), make a focused change, and run the relevant tests.
Use Conventional Commits, a cryptographic signature and a matching DCO sign-off.
Hooks enforce local checks; CI and protected branch gates remain authoritative.
Resolve review findings and repeat independent review before merging with green applicable checks.
User-approved alpha work can proceed without a separate human acceptance gate; never describe agent review as human security review.

## Testing

Unit tests are offline. Native tests use dedicated Docker TCP/HTTP counters and must run serially per shared target.
`HTTP_GUARD_TEST_AUTOLOAD` selects the installed SDK/Core loader; do not substitute mocks for real ABI proof.
`HTTP_GUARD_FIXTURE` identifies a prepared genuine Core fixture for the integration bootstrap.
Preserve historical evidence as history. Record actual versions, exit codes, counts and limits for fresh runs.

## Boundaries

Preserve denial before send, endpoint binding, immutable snapshots, DNS pinning and single-use native handles.
Do not add fallback handlers, suppress dependency advisories or weaken tests to turn a failure green.
Do not edit vendor trees, generated artifacts, captured originals or archived fixture locks.
Do not commit credentials, bypass hooks, push directly to protected main or merge with unresolved findings.
Publication and deployment require the user's authorization; do not infer it from implementation work.
