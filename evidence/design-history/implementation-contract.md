# HTTP Guard implementation contract

Authorization: user requested implementation of the complete plan in conversation 6ac7f920-aa1c-83eb-a215-acdaa85848e9. Original specifications are extracted in work/http-guard-specs; 45 requirements, T001–T084, AP-01–AP-10. No further plan approval is needed. No publishing, merging, production deployment, or memory updates authorized.

Consulted lessons before acting: original specifications, TYPO3/Guzzle source investigation, exact Vault source 5a070c396a614e5b05f63d79fa564c3748cf21eb. Existing Vault checkouts are dirty or stale; use isolated clone. No relevant registry memory was used.

## Charter and deliverables

Implement framework-independent netresearch/http-guard, standalone netresearch/nr-http-guard integration, and an explicit opt-in Vault adapter. Preserve Vault APIs/credentials/audit/streaming/cancellation and legacy defaults. Source deliverables and reproducible evidence under outputs; working dependencies and scripts under work. Production release and a real operator pilot require external acceptance and are not fabricated by tests.

D01: Original specs/ADRs and traceable requirement/test ledger.
D02: G0 integration and controlled transport probe in real TYPO3 13.4/14.3, Guzzle 7/8; instrumented synthetic targets.
D03: Shared library, strict URI/address/DNS/grant/options/redirect policy and isolated curl-multi transport.
D04: TYPO3 registration, configuration, diagnostics, public/endpoint clients.
D05: Vault characterization and explicit opt-in adapter with preserved functional contracts.
D06: Automated security corpus, wire/parallel/lifecycle/mutation evidence, independent review, installation/migration documentation and truthful gate status.

## Macro sequence

1. Validate original contract and runtime/source capabilities. Create reproducible AP-01 harness; test actual bootstrap and middleware/request/response order, context allowlist, terminal leaf replacement, unsupported registry, Guzzle majors, isolation. G0 exits only with recorded PASS results for real TYPO3 13.4 and 14.3 each with Guzzle 7 and 8, actual Bootstrap::init and RequestFactory, middleware request/response ordering, controlled transport options, handler lifetime/teardown and concurrent transfers with separate wire counters. Missing/unavailable evidence leaves G0 unpassed and stops production implementation of the selected integration path. On failure revise ADR-0003 with evidence before building on that path.
2. AP-02/AP-07 run independently: versioned address data and security scenarios; characterize exact Vault APIs/tests.
3. Build shared library policy/normalization/resolver/grants with failing negative tests; run targeted checks before transport.
4. Build isolated curl-multi transport, option inventory, cancellation/streaming lease and wire tests; use only validated connection plans.
5. Integrate TYPO3 and opt-in Vault separately; require cumulative core allowlist and origin binding.
6. Complete diagnostics/telemetry, shared regression corpus, targeted mutations and independent code review; repair findings.
7. Package source and evidence with complete per-test status, supported combination list, remaining human/production gates.

## Shadow points and mitigations

1. Spec drift: maintain exact HG/T IDs; no summary-only implementation.
2. Registration order: real final bootstrap/request-time verification; first/last own entries and reject duplicate/object registry.
3. Skipped middleware: terminal must be last; prove every other request/response middleware executes.
4. Core allowlist bypass by origin rewrite: envelope comparison at terminal.
5. Stream/custom handler fallback: controlled leaf, reject stream/raw options; document pre-entry handler bypass.
6. DNS rebinding: verify every candidate, pin complete set, no NSS fallback.
7. Proxy inheritance: actual process environment/explicit config inventory; reject before target/proxy contact.
8. Cross-transfer DNS/connection state: isolated multi per attempt and pinned wire counters under parallel requests.
9. Credential leak via redirects: final response boundary check, same-origin default; isolated public-fetch header set.
10. Invalid grant: registry-owned objects, expiry/revision binding, never select via URL/user string.
11. Transport/version drift: exact Guzzle dependency locks, major-specific option inventory and source evidence.
12. Cancellation/partial stream leaks: owner/lease lifetime, cancellation during active sockets, callback error tests.
13. Logging leak/failure: fixed reason messages, no raw request/exception/body/query, bounded events.
14. Unsupported performance/production claims: document measured limits and separate simulated local acceptance from operator pilot/security review by a human.

## MAST coverage

FM-1.1: spec IDs and immutable originals. FM-1.2: separate read-only researchers/validator, owned edits. FM-1.3: per-deliverable statuses and unchanged-test evidence reuse. FM-1.4: durable contract/files. FM-1.5: completion requires evidence; unresolved external gates remain explicit. FM-2.1: refine findings in place. FM-2.2: locate missing originals and clarify needed source only. FM-2.3: no adjacent Vault refactors. FM-2.4: shared source/runtime findings. FM-2.5: reconcile returned research before implementation. FM-2.6: verify actual files against requirements. FM-3.1: no claiming partial prototype as complete. FM-3.2: wire counters and mutation tests. FM-3.3: fresh independent review and real dependency/bootstrap checks.

## Execution contracts

Each increment requires preconditions, a concrete edit, observable outcome, acceptance test, falsification test, dependency and deliverable ID in its recorded evidence. Use in-session execution and independent evaluators; do not invoke another CLI model or create a native goal without user request. Do not write memory. User implementation request is already authorization for local dependencies and reversible source changes implied by this design.

## Gate ledger (initial state)

| Gate | Status | Owner / exit evidence |
|---|---|---|
| G0 | PASS 2026-10-08 | Root inspected all 12 `work/probe/evidence/core{13,14}g{7,8}-{normal,object,duplicate}-final.json` records: four real bootstrap/RequestFactory runs with 35 passing checks each and eight failing-configuration zero-wire cases. Native teardown explicitly proved; G7 uses per-attempt CurlFactory(0) and idempotent destructor, G8 close(). No production acceptance implied. |
| G1 | Pending | Root + implementation agents; all applicable P0 cases with actual results, no synthetic pass ledger |
| G2 | Pending | Root; each required targeted security mutation demonstrably fails a critical test |
| G3 | Pending | Integration agents; cumulative Core policy, middleware contracts, Vault/OAuth/stream/cancel preserved |
| G4 | Pending | Root; offline CLI, telemetry, install/upgrade/rollback scenario evidence |
| G5 | Pending | Root; exact locks/dependency inventory, Composer audit, OS/libcurl security assessment including distributor backports; exclude untested combinations |
| G6 | Pending external human review | Another person than first implementer; automated independent agent review is additional evidence and does not satisfy human acceptance |
| AP-10 operator pilot | Pending external operator | Actual operator installation, reviewed grants and monitoring; local synthetic pilot cannot satisfy production acceptance |

Every run records source commit, dependency-lock hash/resolved versions, PHP, OS/libcurl, config revision, corpus revision and actual results. Source-only reports are not runtime pass evidence. Gate status changes require links to these records.
