.. _development-requirements:

====================================
Requirements and security invariants
====================================

This chapter connects the original design with today's single extension.
Original text, statuses and measurements remain in immutable Git history;
this overview explains the current contract and test layout. Historical
IDs do not claim that all 84 old scenarios have passed again against the
current source. Source-bound current executions are recorded under
:ref:`verification-report` and :ref:`assessment-reconciliation`.

The design contains eight invariants, 45 IDs HG-001–HG-045 and
84 scenarios T001–T084 (74 P0, ten P1). HG-006 now means a
framework-independent kernel embedded in one extension; the revised
packaging decision is under :ref:`decision-single-extension`.

.. _development-requirements-invariants:

Current security contract
=========================

* **INV-01:** Every connection uses only validated IPs from its current
  connection plan.
* **INV-02:** Scheme, host, port, method, endpoint profile and policy revision
  bind the plan; changes invalidate it.
* **INV-03:** Invalid targets, missing IPs or unsafe runtime conditions fail
  before contact in enforce.
* **INV-04:** Internal grants require an explicitly bound client and endpoint.
* **INV-05:** Redirects and retries are checked again; a URL is no lasting
  authorization.
* **INV-06:** Policies combine restrictions; denial takes precedence over
  permissions.
* **INV-07:** Grants, pins and connections cannot cross request, client or
  policy boundaries.
* **INV-08:** Unprotected modes and paths outside integration receive no
  protection claim.

.. _development-requirements-ids:

Original requirement ID overview
================================

Short topics and mappings come from the immutable requirement ledger.
The scenario column preserves its mapping without introducing new statuses.

.. list-table:: Original HG/T mapping
    :header-rows: 1

    * - Requirement ID and short topic
      - Original scenarios
    * - HG-001: Outgoing HTTP extension point
      - T001
    * - HG-002: Guard before each native send
      - T002, T003, T004, T083
    * - HG-003: Default enforce and fail closed
      - T005, T006
    * - HG-004: No implicit internal grants
      - T005
    * - HG-005: Separate supported Core targets
      - T001, T041, T082
    * - HG-006: Framework-independent embedded kernel
      - T007
    * - HG-007: Shared strict URL normalization
      - T008, T010
    * - HG-008: Reject ambiguous or non-HTTP targets
      - T009, T010, T011, T012
    * - HG-009: Effective authority binding
      - T004, T013
    * - HG-010: Binary address classification
      - T014, T015, T016, T020
    * - HG-011: Deny special-purpose networks
      - T014, T015, T017, T018, T019, T020
    * - HG-012: Operator CIDR deny precedence
      - T021
    * - HG-013: Check every A/AAAA candidate
      - T022, T023, T028
    * - HG-014: Verified resolution required
      - T024, T025, T026, T027
    * - HG-015: Only validated connection plans
      - T026, T027, T029, T030, T033, T084
    * - HG-016: Pin complete permitted address sets
      - T030, T031, T032
    * - HG-017: Explicit endpoint/client binding
      - T025, T027, T034, T035, T036, T037, T038, T081
    * - HG-018: No user-controlled grants
      - T034, T035, T036
    * - HG-019: Cumulative Core and endpoint restrictions
      - T002, T004, T021, T039, T083
    * - HG-020: Reject unsafe transport options
      - T040, T041
    * - HG-021: Controlled cURL capabilities
      - T042
    * - HG-022: Detect and reject proxy routes
      - T043, T044
    * - HG-023: Recheck each redirect
      - T045, T046, T048, T049, T051, T081
    * - HG-024: Restrict redirected credentials
      - T046, T047, T048, T050
    * - HG-025: Recheck retries without implicit budget
      - T052
    * - HG-026: Isolate native DNS and connection caches
      - T053, T054, T055, T084
    * - HG-027: Bound verified memoization
      - T056, T057
    * - HG-028: Refuse generic streaming fallback
      - T058
    * - HG-029: Deliberate streaming adapter
      - T059, T060
    * - HG-030: Cancellation and cleanup
      - T060, T061
    * - HG-031: Typed synchronous and asynchronous errors
      - T062
    * - HG-032: Redacted and bounded reporting
      - T063, T064
    * - HG-033: Visible observe/disabled limits
      - T065, T066
    * - HG-034: Offline diagnostics
      - T067
    * - HG-035: URL check grants no reusable authority
      - T068
    * - HG-036: Deliberate external Vault integration
      - T069, T070
    * - HG-037: Preserve external Vault semantics
      - T059, T060, T070, T071, T072
    * - HG-038: Legacy allowlists grant no global access
      - T039, T073
    * - HG-039: Actual no-contact witnesses
      - T074
    * - HG-040: Concurrent and long-worker isolation
      - T053, T054, T075
    * - HG-041: Shared corpus with explicit integration scope
      - T074, T076
    * - HG-042: Declare uncovered paths
      - T003, T006, T077
    * - HG-043: Explicit versioned migration
      - T038, T066, T073, T080
    * - HG-044: Document time and memory bounds
      - T028, T057, T078, T079, T082
    * - HG-045: Reproducible qualified regressions
      - T076, T080

.. _development-requirements-tests:

Current test and build entry points
===================================

These groups cover every original scenario ID and identify current checks
for the same topics. Unit, genuine Core, wire, mutation and performance
results remain distinct; a filename does not establish execution.
The short test names below refer to :file:`Tests/Unit/`,
:file:`Tests/HttpGuard/Unit/Policy/` or
:file:`Tests/HttpGuard/Unit/Transport/`; filenames end in .php.

* T001–T007: CoreStackProviderContractTest, RequestFactoryAbiContractTest,
  GuardConfigurationBoundaryContractTest; genuine Core paths under
  :file:`Tests/Functional/`, preparation under :file:`Build/Fixtures/`.
* T008–T021: TargetAuthorityBoundaryContractTest,
  CidrMembershipBoundaryContractTest and AddressRulesBoundaryContractTest.
* T022–T033: ResolverAnswerBoundaryContractTest and
  PolicyPlanBoundaryContractTest; actual DNS and pinning under
  :file:`Tests/HttpGuard/Integration/DnsPolicyTransportTest.php`.
* T034–T044: ClientFactoryContractTest, OptionSanitizerContractTest and
  MiddlewareRegistryContractTest; native option/proxy boundaries under
  :file:`Tests/HttpGuard/Integration/ProductionTransportTest.php`.
* T045–T057: InvocationLifecycleTest and ResolverResidualCacheContractTest;
  redirects, retries and isolation in ProductionTransportTest.php.
* T058–T064: TransferLeaseLifecycleContractTest,
  TransferLeaseRuntimeBoundaryContractTest and DecisionReporterContractTest.
  The Vault portions of T059–T061 retain external historical qualification.
* T065–T073: DiagnosticsContractTest, CommandContractTest,
  ManagedAuthenticationContractTest and :file:`Tests/Functional/mode-bootstrap.php`.
  T070–T072 require Vault; kernel tests do not replace its complete suite.
* T074–T077: Actual no-contact/concurrency witnesses in integration tests
  and versioned corpus under :file:`Resources/Private/HttpGuard/data/`.
  T076's Vault portion remains historical; T077 describes uncovered paths.
* T078–T084: WireDnsQueryTest, ManagedAuthenticationContractTest and
  CoreStackProviderContractTest; policy benchmark under
  :file:`Build/Scripts/benchmark-policy.php`, mutation via
  :file:`infection.native.json5` and :file:`.github/workflows/verification.yml`.

T079's named CI policy measurement is under :ref:`assessment-reconciliation`.
It is not an end-to-end HTTP benchmark. Historical targeted mutants and
global Infection MSI scores remain distinct evidence. The optional
Vault reference under :ref:`api-vault` is outside this production extension
and does not establish a freshly executed release of the external project.

Maintained source-bound reports are under
:file:`Build/Reports/Assessment/completion/reconciliation.json`; current
tests are under :file:`Tests/` and tools under :file:`Build/`. These
development files are not TER runtime dependencies.

.. _development-requirements-sources:

Immutable historical sources
============================

The 28 original specification files and raw receipts remain at commit
:literal:`3f929ae8794ca04ab3dd25627e94d601a2db23e0` and in the verified
external source archive (SHA-256 below). This manual does not copy
conflicting old API proposals or historical passing statuses.

* `Original specifications and proposed ADRs
  <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/README.md>`_
* `Original ledger with complete 45/84 mappings
  <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/verification/requirements-and-tests.md>`_

:literal:`2e417da29df5348e284a93f1db96b2a067426dc5ce4e7ee74f978375d6eb34c9`
