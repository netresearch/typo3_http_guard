# Original plan: consolidated verification ledger

Recorded 2026-10-09. Preserved exactly **45 requirements / 84 scenarios / 8 invariants**. **74/74 P0 and 9/10 P1** have current scenario evidence; T079 remains partial for a named CI reference-system benchmark. G5 legacy advisories, G6 human security review and AP-10 operator pilot remain separate release conditions.

The standalone source-bound matrix passes 122 tests / 2167 assertions in each of eight PHP8.2–8.5/Guzzle7–8 tuples. Actual Core integration passes four exact tuples; frozen Vault source passes both dependency majors. Scope and source hashes are recorded in each component report. Raw URI text erased by an existing PSR18 creator cannot be recovered; policy changes activate through immutable snapshot reconstruction.

All paths below are relative to the delivery root. Detailed behavior, exact source locators and covered limits are in the JSON ledger.

## Updated single-package delivery

The original package split was explicitly replaced by one extension with an
embedded core and complete documentation. The later qualification evidence
supplements the unchanged historical execution records:

- Twelve PHP/SDK cells: 126/2180 each, with no skips, errors or failures.
- 18 targeted mutants with actual native and HTTP witnesses.
- 252 Core matrix processes: 168 Composer and 84 genuine classic installations;
  210 wire assertions. Final ZIP reinstallation adds another 70 separately.
- Vault packaging smoke tests: 19/249 and 19/250. Historical complete suites
  explicitly retain their original source revision.

Source paths now follow the root extension layout. Historical execution manifests
are mapped through `evidence/packaging/source-layout-map.json`. HG-006 remains
the dependency boundary of the embedded core; the user's packaging decision
supersedes a separate second production package. The JSON ledger records
current component references and exact provenance. The counts in this ledger
are recorded qualification results, not a claim of re-execution during its
English translation.

## Tests

| ID | Priority | Status | Original scenario (English translation) | Evidence / remaining gap |
|---|---|---|---|---|
| T001 | P0 | verified_scenarios | Registration in real TYPO3 13.4 and 14.3 instances | evidence/typo3-integration/production-matrix-summary.json  |
| T002 | P0 | verified_scenarios | Custom middleware before and after the Core allowlist | evidence/typo3-integration/production-matrix-summary.json  |
| T003 | P0 | verified_scenarios | Middleware after the terminal; duplicate registration; HandlerStack object | evidence/typo3-integration/production-matrix-summary.json  |
| T004 | P0 | verified_scenarios | Middleware rewrites the origin after the Core check | evidence/typo3-integration/production-matrix-summary.json  |
| T005 | P0 | verified_scenarios | Empty new configuration | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T006 | P0 | verified_scenarios | Invalid mode or unknown configuration fields | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T007 | P1 | verified_scenarios | Install the library without TYPO3 and nr-vault | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T008 | P0 | verified_scenarios | Canonical public host, uppercase spelling and one endpoint | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T009 | P0 | verified_scenarios | file, gopher, ftp, dict, relative URL, userinfo and fragment | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T010 | P0 | verified_scenarios | Control characters, backslash, percent-encoded host and empty labels | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T011 | P0 | verified_scenarios | Integer, hexadecimal, octal and shortened IPv4 | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T012 | P0 | verified_scenarios | IPv6 zone ID and invalid URI brackets | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T013 | P0 | verified_scenarios | Different Host header or port | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T014 | P0 | verified_scenarios | Direct IPv4 RFC1918 and loopback | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T015 | P0 | verified_scenarios | IPv6 ULA, loopback and link-local | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T016 | P0 | verified_scenarios | IPv4-mapped IPv6 with embedded internal and public IPv4 | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T017 | P0 | verified_scenarios | Cloud metadata IPv4/IPv6, unspecified and multicast | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T018 | P0 | verified_scenarios | CGNAT, documentation, benchmark and reserved ranges | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T019 | P0 | verified_scenarios | NAT64, 6to4, Teredo and deprecated compatible addresses | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T020 | P0 | verified_scenarios | CIDR boundaries of all bundled ranges | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T021 | P0 | verified_scenarios | Operator deny range overlaps an endpoint permission | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T022 | P0 | verified_scenarios | DNS returns public A and private AAAA | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T023 | P0 | verified_scenarios | DNS returns public AAAA and private A | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T024 | P0 | verified_scenarios | NXDOMAIN, SERVFAIL, empty answer and invalid record data | evidence/dns-transport/execution-manifest.json  |
| T025 | P0 | verified_scenarios | Approved private host without usable resolution | evidence/dns-transport/execution-manifest.json  |
| T026 | P0 | verified_scenarios | Name known only through hosts/NSS, without a static mapping | evidence/dns-transport/execution-manifest.json  |
| T027 | P0 | verified_scenarios | Static host with a narrow internal permission | evidence/typo3-integration/production-matrix-summary.json  |
| T028 | P0 | verified_scenarios | CNAME to private, loop, more than eight hops or more than 64 addresses | evidence/dns-transport/execution-manifest.json; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T029 | P0 | verified_scenarios | Resolver returns public first, then private | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T030 | P0 | verified_scenarios | Pin to an unreachable address | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T031 | P0 | verified_scenarios | Multiple A/AAAA records, with the first address unreachable | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T032 | P0 | verified_scenarios | Malformed pin string or option setter failure | evidence/dns-transport/execution-manifest.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T033 | P0 | verified_scenarios | TLS SNI, certificate name and Host during pinning | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T034 | P0 | verified_scenarios | ERP grant used for a different origin, port, method or CIDR | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T035 | P0 | verified_scenarios | User supplies a profile string, header or forged grant object | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T036 | P0 | verified_scenarios | Public importer calls a configured ERP host | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T037 | P0 | verified_scenarios | Loopback with /32 or /128 and an explicit flag | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T038 | P0 | verified_scenarios | Grant expiry, changed policy revision and delayed scheduled attempt | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T039 | P0 | verified_scenarios | Core context allowlist together with the new guard policy | evidence/typo3-integration/production-matrix-summary.json  |
| T040 | P0 | verified_scenarios | Caller supplies RESOLVE, CONNECT_TO, URL, SHARE, Unix socket or FOLLOWLOCATION | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/transport/options-final.log  |
| T041 | P0 | verified_scenarios | Guzzle 7/8 options, forbidden delay and Promise/PSR-7 majors | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/transport/options-final.log  |
| T042 | P0 | verified_scenarios | Missing ext-curl, missing curl-multi or incompatible version | evidence/dns-transport/execution-manifest.json; evidence/dns-transport/missing-curl/summary.json; evidence/typo3-integration/production-matrix-summary.json  |
| T043 | P0 | verified_scenarios | Explicit HTTP, HTTPS or SOCKS proxy | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T044 | P0 | verified_scenarios | Proxy through the real environment, including case and NO_PROXY variants; incoming Proxy header checked separately | evidence/dns-transport/execution-manifest.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T045 | P0 | verified_scenarios | Redirect to a private IP and to a private DNS address | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T046 | P0 | verified_scenarios | Same-origin and cross-origin redirects | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T047 | P0 | verified_scenarios | 307/308 with a body secret or custom authentication header to another origin | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T048 | P0 | verified_scenarios | Response middleware changes Location after the terminal | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T049 | P0 | verified_scenarios | Relative or scheme-relative Location, downgrade, loop and request limit above/below the operator limit | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T050 | P0 | verified_scenarios | Public Fetch across multiple public origins | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T051 | P0 | verified_scenarios | allow_redirects=false or PSR-18 send | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T052 | P0 | verified_scenarios | Retry middleware repeats a request after a network failure | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T053 | P0 | verified_scenarios | Concurrent same host/port with different policy and IP | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T054 | P0 | verified_scenarios | Long-lived worker with an internal permission followed by a public request | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T055 | P0 | verified_scenarios | External or persistent transport sharing | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T056 | P0 | verified_scenarios | Memoization hit after a policy change | evidence/dns-transport/execution-manifest.json; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T057 | P1 | verified_scenarios | TTL 0, expiry, capacity and negative answer | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T058 | P0 | verified_scenarios | stream=true despite installed cURL | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T059 | P0 | verified_scenarios | Vault sendStreaming with a valid pin | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T060 | P0 | verified_scenarios | Cancellation before send and during transfer | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T061 | P0 | verified_scenarios | Body close, partial read, transfer failure and callback exception | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; integrations/nr-vault/evidence/vault-guzzle7-final-unit-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-unit-frozen.log  |
| T062 | P0 | verified_scenarios | Synchronous and asynchronous policy errors, PSR-18 | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; Resources/Private/HttpGuard/data/security-corpus/evidence/vault-guard-adapter-wire-complete.log  |
| T063 | P0 | verified_scenarios | Secrets in query, body, headers, certificate paths and exception | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; Resources/Private/HttpGuard/data/security-corpus/evidence/vault-guard-adapter-wire-complete.log  |
| T064 | P1 | verified_scenarios | Logger failure and denial flood | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; Resources/Private/HttpGuard/data/security-corpus/evidence/vault-guard-adapter-wire-complete.log  |
| T065 | P0 | verified_scenarios | Observe with a forbidden target or unsupported transport | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T066 | P1 | verified_scenarios | Disabled and deliberate rollback | evidence/typo3-integration/production-matrix-summary.json; Resources/Private/HttpGuard/data/security-corpus/evidence/vault-guard-adapter-wire-complete.log  |
| T067 | P1 | verified_scenarios | Doctor with a normal or malformed stack | evidence/typo3-integration/production-matrix-summary.json; evidence/typo3-integration/final-execution-source-hashes.json  |
| T068 | P1 | verified_scenarios | Policy-check with and without an endpoint | evidence/typo3-integration/production-matrix-summary.json; evidence/typo3-integration/final-execution-source-hashes.json  |
| T069 | P0 | verified_scenarios | Only the global extension installed, with Vault unchanged | evidence/typo3-integration/production-matrix-summary.json  |
| T070 | P0 | verified_scenarios | Vault with an explicitly integrated adapter | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T071 | P0 | verified_scenarios | Internal OAuth token endpoint with external resource endpoint, and vice versa | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T072 | P0 | verified_scenarios | Vault authentication types and redacted audit | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T073 | P0 | verified_scenarios | Legacy report for flat and nested allowed_hosts | evidence/typo3-integration/production-matrix-summary.json  |
| T074 | P0 | verified_scenarios | Mutation: check disabled or pin removed | evidence/transport/mutations/summary.json  |
| T075 | P0 | verified_scenarios | Concurrency with changing DNS answers and grants | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T076 | P1 | verified_scenarios | Shared regression test in the library, TYPO3 and Vault | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json; integrations/nr-vault/evidence/vault-guzzle7-shared-unbound.json; integrations/nr-vault/evidence/vault-guzzle8-shared-unbound.json  |
| T077 | P0 | verified_scenarios | Third-party SDK, direct cURL and request-owned handler | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T078 | P1 | verified_scenarios | DNS blackhole or long resolver call | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T079 | P1 | partial_current_evidence | Classification with 64 addresses and 128 profiles, and large responses | Resources/Private/HttpGuard/data/security-corpus/evidence/policy-benchmark-final.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log Named CI reference-system benchmark remains external to the current host measurement. |
| T080 | P0 | verified_scenarios | Dependency update changes handlers or raw options | evidence/dns-transport/execution-manifest.json; verification/evidence/library-matrix/php82g7.results.junit.xml; verification/evidence/library-matrix/php82g8.results.junit.xml; verification/evidence/library-matrix/php83g7.results.junit.xml; verification/evidence/library-matrix/php83g8.results.junit.xml; verification/evidence/library-matrix/php84g7.results.junit.xml; verification/evidence/library-matrix/php84g8.results.junit.xml; verification/evidence/library-matrix/php85g7.results.junit.xml; verification/evidence/library-matrix/php85g8.results.junit.xml; evidence/typo3-integration/production-matrix-summary.json; integrations/nr-vault/EVIDENCE.md  |
| T081 | P0 | verified_scenarios | POST-to-GET redirect with a restricted method profile | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T082 | P1 | verified_scenarios | TLS with a custom CA, mTLS and policy-controlled verify=false | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T083 | P0 | verified_scenarios | Order of request and response middleware | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T084 | P0 | verified_scenarios | cURL Alt-Svc, HSTS and automatic protocol or route changes | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |

## Requirements

| ID | Status | Original requirement (English translation) | Test IDs |
|---|---|---|---|
| HG-001 | scenario_evidence_verified | The extension MUST use the documented outbound Guzzle middleware extension point; it MUST NOT use incoming PSR-15 middleware. | T001 |
| HG-002 | scenario_evidence_verified | In the protected default stack, every actual send attempt MUST pass through the guard immediately before the controlled transport. | T002, T003, T004, T083 |
| HG-003 | scenario_evidence_verified | The default mode MUST be `enforce`; missing or invalid configuration MUST NOT allow unprotected sending. | T005, T006 |
| HG-004 | scenario_evidence_verified | New installations MUST start without internal permissions. Existing project configuration MUST NOT automatically be reinterpreted as permissions. | T005 |
| HG-005 | scenario_evidence_verified | Version 1 MUST treat TYPO3 13.4 and 14.3 as separate test targets; concrete minimum patch versions are demonstrated by Composer and CI. | T001, T041, T082 |
| HG-006 | scenario_evidence_verified | The shared library MUST NOT require a TYPO3 or nr-vault dependency. | T007 |
| HG-007 | scenario_evidence_verified | URLs MUST be normalized consistently and strictly before policy and DNS evaluation. | T008, T010 |
| HG-008 | scenario_evidence_verified | Only absolute HTTP/HTTPS targets with a valid host and port are allowed. Userinfo, fragments, zone IDs and ambiguous numeric IP forms MUST be rejected. | T009, T010, T011, T012 |
| HG-009 | scenario_evidence_verified | URI authority and the effective HTTP host MUST match; arbitrary Host-header rerouting is outside the standard product. | T004, T013 |
| HG-010 | scenario_evidence_verified | IPv4, IPv6 and IPv4-mapped IPv6 MUST be classified in binary form and under the same policy. | T014, T015, T016, T020 |
| HG-011 | scenario_evidence_verified | The public profile MUST block private, local, link-local, multicast, documentation, benchmark and other excluded special-purpose ranges. | T014, T015, T017, T018, T019, T020 |
| HG-012 | scenario_evidence_verified | Additional operator deny CIDRs MUST be supported and MUST take precedence over permissions. | T021 |
| HG-013 | scenario_evidence_verified | Every usable A/AAAA candidate MUST be checked. A forbidden candidate in an answer MUST reject the entire attempt. | T022, T023, T028 |
| HG-014 | scenario_evidence_verified | Empty, malformed or unusable resolution MUST fail closed; an endpoint permission MUST NOT bypass this. | T024, T025, T026, T027 |
| HG-015 | scenario_evidence_verified | The transport MUST use only the validated ConnectionPlan; independent destination resolution is forbidden. | T026, T027, T029, T030, T033, T084 |
| HG-016 | scenario_evidence_verified | Multiple permitted IPs MUST be retained in a valid pin for each host/port pair; dual-stack fallback remains restricted to that set. | T030, T031, T032 |
| HG-017 | scenario_evidence_verified | Internal exceptions MUST cover the origin, port, methods, permitted IP sets and an explicitly bound client. | T025, T027, T034, T035, T036, T037, T038, T081 |
| HG-018 | scenario_evidence_verified | A plain URL, freely copied request header or user-supplied context string MUST NOT activate an internal permission. | T034, T035, T036 |
| HG-019 | scenario_evidence_verified | The Core allowlist, global denials and endpoint policy MUST apply cumulatively. A narrower policy MUST NOT be replaced by a broader one. | T002, T004, T021, T039, T083 |
| HG-020 | scenario_evidence_verified | Caller-supplied raw transport options relevant to security MUST be rejected; the guard generates its own options. | T040, T041 |
| HG-021 | scenario_evidence_verified | The protected transport MUST use cURL/curl-multi. Missing support or unknown handlers MUST fail before send in enforce mode. | T042 |
| HG-022 | scenario_evidence_verified | Explicit and environment-inherited proxy configuration MUST be detected. Version 1 MUST NOT send through a proxy or silently bypass it. | T043, T044 |
| HG-023 | scenario_evidence_verified | Every redirect MUST undergo complete checking again; cURL-native redirects that bypass the stack are forbidden. | T045, T046, T048, T049, T051, T081 |
| HG-024 | scenario_evidence_verified | Redirects carrying credentials MUST remain on the same origin; the generic default client conservatively uses same-origin redirects. | T046, T047, T048, T050 |
| HG-025 | scenario_evidence_verified | Every retry MUST pass through policy again. Policy denials are not retryable; the guard MUST NOT silently increase retry budgets itself. | T052 |
| HG-026 | scenario_evidence_verified | Connection and DNS caches MUST be isolated between independent transfers. Shared cURL share handles are forbidden in version 1. | T053, T054, T055, T084 |
| HG-027 | scenario_evidence_verified | DNS memoization MAY reuse only checked addresses, MUST be bounded and MUST check against the current policy again on each use. | T056, T057 |
| HG-028 | scenario_evidence_verified | `stream => true` MUST NOT fall back to PHP streams. Version 1 of the global adapter explicitly rejects the option. | T058 |
| HG-029 | scenario_evidence_verified | An explicitly integrated curl-multi streaming adapter MUST use the same guard and pinning invariant, and preserve cancellation. | T059, T060 |
| HG-030 | scenario_evidence_verified | Cancellation or failures MUST release handlers, sockets and response buffers; no hanging background transfers are allowed. | T060, T061 |
| HG-031 | scenario_evidence_verified | The guard API MUST provide typed, stable denial reasons and a Promise/PSR-18-compliant error interface. | T062 |
| HG-032 | scenario_evidence_verified | Logs MUST NOT contain bodies, credentials, complete URLs, query strings or raw requests. | T063, T064 |
| HG-033 | scenario_evidence_verified | Inspection mode MUST be visibly marked as non-protective. `enforce`, `observe` and `disabled` MUST NOT be confused. | T065, T066 |
| HG-034 | scenario_evidence_verified | A diagnostic command MUST show configuration, ordering, runtime capabilities, proxy state and coverage limits without probe requests. | T067 |
| HG-035 | scenario_evidence_verified | URL checking without sending MUST be possible; it is not authorization that can be reused later. | T068 |
| HG-036 | scenario_evidence_verified | nr-vault MUST be migrated through its own adapter; its separate factory MUST NOT be presented as automatically protected globally. | T069, T070 |
| HG-037 | scenario_evidence_verified | Vault integration MUST preserve credential injection, secret access control, auditing, OAuth token retrieval, timeout, streaming and cancellation behaviour. | T059, T060, T070, T071, T072 |
| HG-038 | scenario_evidence_verified | Existing flat Vault allowlist entries MUST NOT automatically become global or unrestricted internal permissions. | T039, T073 |
| HG-039 | scenario_evidence_verified | Security-critical denials MUST be tested with a destination demonstrably not contacted; an exception alone is insufficient evidence. | T074 |
| HG-040 | scenario_evidence_verified | Concurrent requests and long-lived CLI processes MUST be tested for cross-request, cache and policy leaks. | T053, T054, T075 |
| HG-041 | scenario_evidence_verified | Library, TYPO3 and Vault tests MUST use the same normative security corpus. | T074, T076 |
| HG-042 | scenario_evidence_verified | Unsupported configurations MUST fail visibly. Documentation MUST NOT claim coverage beyond what has been tested. | T003, T006, T077 |
| HG-043 | scenario_evidence_verified | New defaults or removal of Vault legacy behaviour MUST be migrated and versioned as behavioural changes. | T038, T066, T073, T080 |
| HG-044 | partial_evidence | Resource usage and timing MUST have documented limits; synchronous DNS resolution MUST NOT be falsely presented as having hard deadlines. | T028, T057, T078, T079, T082 |
| HG-045 | scenario_evidence_verified | Every security fix MUST have a reproducible regression and an impact check on both integrations. | T076, T080 |

## Security invariants

| ID | Original invariant (English translation) | HG mapping | Test mapping | Status |
|---|---|---|---|---|
| INV-01 | No unchecked address: Every connection attempt uses only addresses from the plan validated for that exact attempt. | HG-013, HG-014, HG-015, HG-016, HG-020, HG-026 | T022, T023, T024, T025, T026, T028, T029, T030, T031, T032, T033, T040, T053, T055, T084 | scenario_evidence_verified |
| INV-02 | Complete target binding: Scheme, canonical host, effective port, method, endpoint profile and policy revision are evaluated together. A change invalidates the previous plan. | HG-007, HG-008, HG-009, HG-017, HG-018, HG-043 | T008, T009, T010, T011, T012, T013, T034, T035, T038, T081 | scenario_evidence_verified |
| INV-03 | Fail closed: A malformed URL, lack of usable addresses, unsupported transport, ordering error or invalid policy leads to rejection before destination contact in enforce mode. | HG-002, HG-003, HG-008, HG-014, HG-020, HG-021, HG-022, HG-042 | T003, T006, T009, T010, T011, T012, T024, T025, T026, T032, T040, T041, T042, T043, T044, T080 | scenario_evidence_verified |
| INV-04 | No implicit exception: Internal permissions apply only to explicitly bound call paths. A mere match between the destination hostname and a global list is insufficient. | HG-004, HG-017, HG-018, HG-038 | T005, T027, T034, T035, T036, T037, T039, T073, T076 | scenario_evidence_verified |
| INV-05 | Every repetition is new: Redirects and retries pass through the same invariants; there is no URL authorization issued once for unrestricted reuse. | HG-023, HG-024, HG-025 | T038, T045, T046, T047, T048, T049, T050, T051, T052, T081 | scenario_evidence_verified |
| INV-06 | Policy composition narrows: Additional restrictions are intersected rather than replaced. Hard denials take precedence. | HG-011, HG-012, HG-017, HG-019 | T004, T017, T021, T034, T039, T083 | scenario_evidence_verified |
| INV-07 | No state transfer: An internal permission, DNS pin or connection MUST NOT transfer to another request, client or policy. | HG-017, HG-018, HG-026, HG-027, HG-040 | T035, T036, T038, T053, T054, T055, T056, T057, T071, T075 | scenario_evidence_verified |
| INV-08 | Honest coverage: No protected label for uncovered clients, observe mode, a disabled guard or unchecked proxy, stream or custom-handler paths. | HG-021, HG-022, HG-028, HG-033, HG-034, HG-036, HG-042 | T042, T043, T044, T058, T065, T066, T067, T068, T069, T070, T077 | scenario_evidence_verified |

## Release gates

- **G0** — passed_root_confirmed: None; initial architecture gate accepted before production implementation.
- **G1** — passed_current_declared_matrix: All 74 P0 scenarios have current component evidence. Execution scope: eight standalone PHP/major tuples, four actual Core/Guzzle tuples on PHP8.5.11, two Vault tuples on PHP8.5.10; this is not every framework/PHP cross-product or broader dependency patch range.
- **G2** — passed: All 12 targeted experiments killed; no surviving mutant.
- **G3** — passed_current_component_integrations: None within recorded actual integration tuples; exact supported/runtime limitations are in their reports.
- **G4** — passed_synthetic_diagnostics: Real operator rollout acceptance remains AP-10; all final diagnostic fixture processes pass.
- **G5** — partial_known_legacy_advisories: Minimal shared-library audits are separate from the existing Vault/historical TYPO3 SVG sanitizer advisories. Functional lower bounds and Ubuntu vendor backports do not establish blanket platform vulnerability freedom; operator dependency/runtime remediation remains required.
- **G6** — external_acceptance_required: Original spec requires security review by a different person. Cross-agent source review finds/fixes defects but does not establish this human gate.
- **AP-10** — external_acceptance_required: Real operator pilot, reviewed endpoint grants, monitoring and deployment acceptance cannot be substituted by synthetic fixtures.
