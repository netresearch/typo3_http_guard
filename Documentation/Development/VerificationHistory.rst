.. _verification-history:

==============================
Historical evidence and limits
==============================

These sections retain the original qualification's measurements and
acceptance model. The user has since authorized alpha development and
merging after independent agent review and green applicable checks without
an additional human approval or operator-pilot gate. Neither activity is
claimed completed. The published alpha and current dependency resolutions
are described in :ref:`assessment-reconciliation` and
:ref:`dependency-report-current`.

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
installable package. The active overview and current test entry points are
in this manual under :ref:`development-requirements`.

.. _verification-limits:

Limits and acceptance
=====================

The microbenchmark on the previously recorded WSL host used 200 samples,
64 IPv6 addresses and 128 profiles. It measured p95 at 1.455 ms, excluding
DNS, network and logging. That historical run did not satisfy T079's named
CI reference. The later source-bound CI result is recorded separately under
:ref:`assessment-reconciliation`. The later transport runs
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

The archived AP-10 proposed an operator test instance and approved internal
endpoints. Its local alpha had not yet been published; version 0.1.1 is now
on TER and Packagist. The authorized current alpha scope defers the human
review and operator pilot, without claiming either occurred. The complete
operational manual is under :ref:`operations`; historical dependency
findings retain their separate scope under :ref:`dependency-report`.
