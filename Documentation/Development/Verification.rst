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

The verification qualifies the recorded Linux/PHP/SDK tuples and actual
Core paths. It does not qualify every framework/PHP cross-product, hosting
environment or future patch version. Unknown and mixed SDK tuples remain
blocked before native send. Early bootstrap requests, third-party SDKs,
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
