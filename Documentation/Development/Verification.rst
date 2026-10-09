.. _verification-report:

===================
Verification report
===================

.. note::
    This page translates the recorded qualification report dated 9 October
    2026. Its source is the unchanged
    `German report at commit 7a3a39b
    <https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/docs/Pruefbericht.md>`_.
    Counts and results below belong to the source revisions, dependency locks
    and archives recorded in that report. Translating and moving the report
    does not claim another execution against subsequent repository changes.
    See :ref:`development-assessment` for the separate repository assessment.

.. _verification-semantic-support:

Semantic support and current proof
==================================

Production supports the semantic ranges in :ref:`installation-requirements`.
Compatible Core and SDK patches and minors do not require an extension
release. PHP :literal:`^8.2` permits compatible future PHP versions, subject
to Core's own requirements; this is not a claim that future PHP releases
have already been executed in the test matrix.

The Core guard checks the actual parent API before loading the replacement
subclass, including readonly compatibility and the callable constructor.
Additional optional constructor arguments remain compatible. SDK guards
check supported minima and majors, public methods and the actual middleware
storage shape. Missing capabilities and incompatible APIs fail closed.

Each lease uses the public :literal:`CurlFactoryInterface` through a
single-use factory. The attempt is consumed before body preparation; a
second creation is denied before the delegate can allocate a native handle.
Ordinary middleware retries and redirects create fresh leases. The native
hidden retry fence no longer relies on Guzzle's private retry counter or
private method names.

The final reviewed executable source passes **201 tests and 2,405 assertions**
in each of five complete Unit/native executions on the pinned PHP 8.5.10
image with PHPUnit 11.5.57. Each execution comprises **152 Unit tests and
1,329 assertions**, plus **49 integration tests and 1,076 assertions**,
without errors, failures or skips. All 108 bound production, test and PHPUnit
configuration inputs have identical hashes at the start and end of the runs.

.. list-table:: Actually executed Core and SDK versions
    :header-rows: 1

    * - Execution
      - Core
      - Guzzle / Promises / PSR-7
    * - Guzzle 7 minimum
      - 13.4.36
      - 7.15.2 / 2.5.1 / 2.13.0
    * - Historical Guzzle 7 archive snapshot
      - 14.3.8
      - 7.15.3 / 2.5.2 / 2.13.0
    * - Fixed Guzzle 7 snapshot
      - 14.3.8
      - 7.15.5 / 2.5.3 / 2.13.1
    * - Fixed Guzzle 8 snapshot
      - 14.3.8
      - 8.2.0 / 3.0.2 / 3.1.0
    * - Separately resolved Guzzle 8 minimum
      - 14.3.8
      - 8.2.0 / 3.0.2 / 3.1.0

These are five executions covering four distinct SDK version tuples; the
separate Guzzle 8 minimum resolution selected the same SDK versions as the
fixed Guzzle 8 snapshot. They establish behavior for those actual versions,
without claiming execution against future compatible releases.

The final Composer Core matrix is a separate execution against the corrected
parent ABI guard: **168 processes, 140 wire assertions and 156 offline
TCP/HTTP no-contact witnesses**. The shared curated Core 13 and Core 14
PHPStan profiles also finish with zero errors. The public Core by-reference
ABI regression passes **19 tests and 31 assertions** after its four-case
reproduction exposed the incompatible parent signatures. Security-floor,
future-minor acceptance and warning-handler restoration mutants fail their
expected controls. Classic installation evidence is recorded separately in
:file:`Build/Reports/Assessment/review-loop/semantic-qualification/summary.json`;
the classic results below retain their earlier source binding.

At the earlier factory module handoff, the targeted suite passed
**five tests and 23 assertions** on each
of actual Guzzle 7.15.3, 7.15.5 and 8.2.0, using PHP 8.5.11 and PHPUnit
11.5.57. A real public :literal:`CurlFactory::finish` control creates a
second native handle without the fence; the guarded case rejects it before
allocation. The tests do not tick the native handler or perform network I/O.
Removing the fence causes the guarded witness to fail on both current
Guzzle majors. These targeted counts are separate from full transport and
genuine Core process suites.

Three historical fixed SDK snapshots and 14 fixed CI cells are retained.
The native verification matrix adds a Guzzle 7 minimum row alongside the
three snapshots and four floating rows, for eight native rows in total.
The four floating Core 13/14 and Guzzle 7/8 rows resolve the
latest compatible graphs during pull requests and weekly scheduled runs.
Preflight checks verify the declared ranges and fixture consistency;
installed-runtime checks inspect the real resolved Core/SDK capabilities.
Current source bindings and execution reports are recorded in the
`semantic compatibility evidence
<https://github.com/netresearch/typo3_http_guard/tree/main/Build/Reports/Assessment/review-loop/semantic-runtime>`_.
Local results and committed workflow definitions do not establish success
of a later remote run against another commit.

.. _verification-current-core:

Patched Core snapshot before semantic ranges
============================================

The earlier review snapshot at commit :literal:`e69ddad` used exact Core
targets **13.4.36 and 14.3.8**. The results in this section belong to that
source before the semantic-contract correction. Both patched
Core releases resolve SVG sanitizer 1.0.0 without advisory exceptions.
RequestFactory and GuzzleClientFactory sources are byte-identical to their
previous patch revisions. That snapshot retained exact Core and complete
SDK-combination checks as well as its parent ABI checks.

Fresh genuine Composer installations with Guzzle 7.15.5 / Promises 2.5.3 /
PSR7 2.13.1 and Guzzle 8.2.0 / Promises 3.0.2 / PSR7 3.1.0 pass the
four-cell matrix: **168 processes, 140 wire assertions and 156 offline
TCP/HTTP no-contact witnesses**. These results exercise actual Core
bootstrap, dependency injection, RequestFactory, mode changes and CLI paths.

Both current official classic archives pass their separate two-cell matrix:
**84 processes, 70 wire assertions and 78 offline TCP/HTTP no-contact
witnesses**. Both fixtures begin with the extension inactive. Real Core
activation persists its exact installation path in PackageStates and
generates the class-loading cache through PackageManager; the persisted
files remain byte-identical after the full matrix. The extension
classes are loaded from the ZIP extraction; no Composer command or source
symlink installs the extension into those sites.

The refreshed combined Unit/native suite passes **145 tests and 2,253
assertions for each of all three exact SDK tuples** on PHP 8.5.10 with
PHPUnit 11.5.57. The Guzzle 7.15.3 / Promises 2.5.2 / PSR7 2.13.0 tuple
remains additional kernel qualification; current classic archives use the
Guzzle 8 tuple. These test counts are separate from the genuine Core process
matrices. They do not assign historical coverage or mutation measurements
to the new source.

Fresh PHPStan 2.3.1 level 8 analysis passes for all four genuine Core/SDK
integration configurations and all three kernel SDK configurations, with
zero errors. The Guzzle 7 template stub supplies only missing class-level
metadata; runtime SDK methods and properties keep their real signatures.
No diagnostic suppression or baseline is used.

The CI preflight checks the committed constraints against the qualified
Core/SDK matrix before installing a cell. The separate security workflow
also checks the actually resolved production runtime before auditing and
generating each Core target's SBOM. These committed guards do not establish
that fresh remote GitHub jobs have already passed.

Current source bindings, runtime summaries and review corrections are kept
in the source repository's
`review-loop evidence
<https://github.com/netresearch/typo3_http_guard/tree/main/Build/Reports/Assessment/review-loop>`_.
These optional reports are excluded from the installable ZIP. The historical
counts below retain their original source, dependencies and archive revisions.

.. _verification-original-report:

Original combined-source report
===============================

The recorded delivery is **one** TYPO3 extension,
:literal:`netresearch/nr-http-guard`, with its embedded security core and
complete manual. No additional HTTP Guard library package is required. The
explicit user requirement supersedes the packaging proposal in ADR-0002;
the recorded
`decision
<https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/evidence/packaging/single-package-decision.md>`_
and
`source layout map
<https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/evidence/packaging/source-layout-map.json>`_
document that change. The original eight specifications and fourteen ADRs
remain unchanged.

.. _verification-recorded-execution:

Recorded execution against the combined source
==============================================

.. list-table:: Qualification results in the archived report
    :header-rows: 1

    * - Area
      - Evidence against the combined source revision
    * - Kernel matrix
      - All twelve combinations of PHP 8.2.33, 8.3.33, 8.4.25 and 8.5.10
        with three exact SDK tuples: **126 tests and 2,180 assertions** each,
        with no skips, errors or failures. The third combination,
        7.15.3 / 2.5.2 / 2.13.0, matches the official classic Core archives.
        See the
        `matrix report
        <https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/verification/evidence/extension-matrix/README.md>`_.
    * - Targeted protection faults
      - All **18** mutants detected: six targeted faults for each of the
        three SDK tuples. Evidence requires a failing test, an actual
        additional HTTP contact and a native attempt or bypassed leaf call.
        The root agent independently recalculated all witnesses. See the
        `mutation report
        <https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/verification/evidence/extension-mutations/README.md>`_.
    * - TYPO3 with Composer
      - Four real Core/SDK instances: **168 processes and 140 wire
        assertions**, plus 156 offline checks without additional TCP or
        HTTP contacts. One extension provides both namespaces; no separate
        library package is installed. New locks and test runs are under
        :file:`evidence/packaging/` in the recorded source repository.
    * - Classic TYPO3
      - Official Core archives **13.4.35 and 14.3.7**, real Extension Manager
        activation, SQLite project setup, persisted PackageStates and
        Core-generated class-loading information. **84 processes and
        70 wire assertions**, plus 78 offline checks without additional TCP
        or HTTP contacts. Classes physically come from the ZIP installation
        directory.
    * - Recorded extension ZIP
      - The final ZIP in this report contained 106 files and was reinstalled
        in both classic installations. Each passed 14 class/source checks
        and 35 actual Core wire assertions. Those additional 70 wire
        assertions are recorded separately from the 252-process matrix.
        No additional Composer installation, source symlinks or private SDK
        vendor directory was present in the extension ZIP. The archive
        contained exactly one production manifest, core classes, its own
        policy data, licenses and manual. See the
        `packaging evidence
        <https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/evidence/packaging/README.md>`_.
    * - Combined unit suite
      - **96 tests / 1,173 assertions** with actual Core 14 / G7 and
        Core 14 / G8 dependencies. Root PHPUnit loads the adapter and the
        embedded core.
    * - Static analysis
      - Kernel PHPStan level 8 without a baseline or errors; actual Core 14
        integration at level 8 without errors. The Core 13 declaration
        required only for analysis is under :file:`Build/`; it belongs
        neither to production nor to the ZIP.
    * - nr-vault
      - Adapter smoke tests: G7 **19/249**, G8 **19/250**; guard unit
        **9/50**, API snapshot **1/247**, PHPStan without errors, clean
        Rector, and CGL with no changes to 595 files. The 25-file patch
        applied cleanly to the recorded base commit; all overlay hashes
        matched. See the
        `Vault evidence
        <https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/integrations/nr-vault/EVIDENCE.md>`_.
    * - Documentation
      - Complete manual directly in the recorded package: **13 RST pages
        and three PHP examples**, :file:`guides.xml` and license notices.
        The official TYPO3 renderer produced no warnings. Examples were
        checked against the actual GuardConfig, interfaces and PolicyEngine;
        four invalid inputs were rejected, with no destination HTTP. See the
        `documentation report
        <https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/evidence/packaging/documentation-report.json>`_.

The new kernel cell manifests were also independently compared with their
recorded source files: **3,192 entries, no mismatches**. Exceptions alone
are not the proof. Native construction, TCP accepts and HTTP requests at
separate controlled test destinations demonstrate both permitted sending
and denied contacts. These tests contain no real credentials or production
requests.

.. _verification-historical-evidence:

Earlier evidence
================

Architecture probe G0 ran before the original implementation against four
Core/Guzzle combinations: twelve runs with 148 assertions. It remains the
original architecture evidence.

The earlier eight-cell kernel matrix with 122/2,167 per cell and its twelve
mutants belong to the previous two-package layout. The earlier complete
Vault suites retain their respective recorded source revisions: unit
3,978/14,868; functional 496/2,653 and 496/2,654 respectively; and fuzz
1,612/7,146. They are not presented as another complete execution against
the repackaged source. The later Vault smoke tests and its unchanged
production PHP diffs specifically check the revised package and fixtures.

Unchanged historical execution manifests retain their original paths and
hashes. The source layout map relates them to the newer layout. The
`recorded requirement ledger
<https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/verification/requirements-and-tests.md>`_
preserves all 45 original requirements, 84 scenarios and eight invariants;
it explains the boundary between a framework-independent core and an
installable package. The active English explanation remains at
:file:`verification/requirements-and-tests.md` in the source repository.

.. _verification-limits:

Limits and acceptance
=====================

The microbenchmark on the previously recorded WSL host used 200 samples,
64 IPv6 addresses and 128 profiles. It measured p95 at 1.455 ms, excluding
DNS, network and logging. This is still not a measurement on the named CI
reference system; that part of T079 remains open. The later transport runs
separately include the actual 4 MiB sink test without an additional guard
body buffer.

The historical verification qualifies its recorded Linux/PHP/SDK tuples and actual
Core paths. It does not qualify every framework/PHP cross-product, hosting
environment or future patch version. That historical runtime blocked unknown
and mixed SDK tuples before native send. Current semantic support is
described in :ref:`verification-semantic-support`. Early bootstrap requests, third-party SDKs,
request-owned handler replacement before middleware entry and direct socket
calls need their own integration. Vault is integrated only through its
explicitly selected adapter.

.. list-table:: Acceptance gates recorded in the archived report
    :header-rows: 1

    * - Release gate
      - Recorded state
    * - G0 Architecture
      - Original probe passed; the new package and Core class-loading paths
        were also actually tested.
    * - G1 Security scenarios
      - The 74 P0 scenarios have mapped execution evidence. The later
        twelve core cells and real Composer, classic and Vault paths
        supplement it. T079 CI performance remains open.
    * - G2 Mutations
      - 18/18 against the combined source, with actual contacts.
    * - G3 Integration
      - Actual Core paths and the explicit Vault adapter tested; no automatic
        coverage of unrelated clients.
    * - G4 Operations
      - Offline diagnostics, mode changes and native registration tested.
        Operator rollback in a concrete project remains outstanding.
    * - G5 Dependencies
      - Exact SDK tuples documented and audited. The three existing
        historical SVG sanitizer advisories remain a prerequisite for
        overall acceptance.
    * - G6 Another person
      - Independent human review remains outstanding. Agent reviews do not
        replace this acceptance gate.

AP-10 still requires an actual operator test instance and approved internal
endpoints. The recorded local alpha had not been published to TER or
deployed to an operator project. The complete operational manual belongs
to the extension under :ref:`operations`. See :ref:`dependency-report` for
details of the recorded dependency findings.
