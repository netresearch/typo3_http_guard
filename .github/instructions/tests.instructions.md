---
applyTo: "Tests/**/*.php,Tests/Build/**,Build/Fixtures/**,Build/Mutation/**"
---

# Tests and qualification

Read `AGENTS.md`, `Build/Fixtures/README.md` and `Documentation/Development/Verification.rst`.
Unit tests must run offline. Use only the dedicated controlled destinations for native transport tests.
Run suites serially when they share TCP/HTTP counters, and distinguish policy assertions from actual wire contacts.
Test the defect before fixing it. Add meaningful failure controls for security and compatibility guards.
Do not replace real Core/SDK bootstraps with mocks or alter historical corpora, reports or archived locks.
Only the three exact public synthetic TLS keys are exempt from local secret detection, after exact byte validation.
Record source hashes, actual installed versions, test counts and execution limits; never infer execution from a manifest.
