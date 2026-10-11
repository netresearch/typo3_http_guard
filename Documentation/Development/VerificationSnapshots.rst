.. _verification-snapshots:

=======================
Qualification snapshots
=======================

.. note::
    This page translates the recorded qualification report dated 9 October
    2026. Its source is the unchanged
    `German report at commit 7a3a39b
    <https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/docs/Pruefbericht.md>`_.
    Counts and results below belong to the source revisions, dependency locks
    and archives recorded in that report. Translating and moving the report
    does not claim another execution against subsequent repository changes.
    See :ref:`development-assessment` for the separate repository assessment.

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
four-cell matrix: **168 processes, 140 bootstrap checks and 156 offline
TCP/HTTP no-contact witnesses**. These results exercise actual Core
bootstrap, dependency injection, RequestFactory, mode changes and CLI paths.
The bootstrap counts include expected guard denials with no target contact.

Both current official classic archives pass their separate two-cell matrix:
**84 processes, 70 bootstrap checks and 78 offline TCP/HTTP no-contact
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
      - Four real Core/SDK instances: **168 processes and 140 bootstrap
        checks**, plus 156 offline checks without additional TCP or
        HTTP contacts. One extension provides both namespaces; no separate
        library package is installed. Locks and test runs remain in the
        immutable packaging evidence linked below.
    * - Classic TYPO3
      - Official Core archives **13.4.35 and 14.3.7**, real Extension Manager
        activation, SQLite project setup, persisted PackageStates and
        Core-generated class-loading information. **84 processes and
        70 bootstrap checks**, plus 78 offline checks without additional TCP
        or HTTP contacts. Classes physically come from the ZIP installation
        directory.
    * - Recorded extension ZIP
      - The final ZIP in this report contained 106 files and was reinstalled
        in both classic installations. Each passed 14 class/source checks
        and 35 actual Core bootstrap checks. Those additional 70 bootstrap
        checks are recorded separately from the 252-process matrix.
        These counts include expected guard denials. No additional Composer
        installation, source symlinks or private SDK
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

