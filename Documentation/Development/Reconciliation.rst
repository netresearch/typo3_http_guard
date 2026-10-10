.. _assessment-reconciliation:

==========================
Assessment reconciliation
==========================

.. _reconciliation-inventory:

Frozen inventory and counting
=============================

The sealed inventory at :literal:`e2e125f` contains **940 distinct IDs**
from eleven relevant catalogs. Catalog hashes and historical raw outcomes
are preserved. A per-ID decision covers every declared ID; there are no
missing or duplicate decisions. The subsequent documentation copy starts
at :literal:`475ef55`, after reviewed dependency PR 10.

.. list-table:: Inventory types
    :header-rows: 1

    * - Type
      - IDs
      - Meaning
    * - Mechanical
      - 756
      - 755 executed entries and gated-out AH-37; the gate is not a pass.
    * - Actual model-review checkpoints
      - 180
      - Recorded agent decisions, distinct from command results.
    * - Definition issues
      - 4
      - SA-55 through SA-58 define executable conditions in a model section.

The original 755 mechanical results are 548 passes, 164 failures and 43
skips. The sealed later mechanical run records **617 passes, 117 failures
and 21 skips**, with no blocked entries. The original 180 model reviews
record 65 passes, 28 findings, 81 applicability/scope skips, three deferred
release items and three requests for evidence. The four misplaced
executable checks were run separately and passed; their catalog definition
issue remains. No fresh model answers are implied by this inventory.

.. list-table:: Frozen per-ID reconciliation
    :header-rows: 1

    * - Classification
      - IDs
    * - Satisfied
      - 378
    * - Already fixed
      - 89
    * - Not applicable
      - 358
    * - Open and applicable
      - 108
    * - External gate
      - 3
    * - Criterion defect
      - 4

These classifications total 940. They are applicability decisions, not raw
pass counts or a compliance percentage. Alternate valid evidence is not
reported as a new fix. A skip, external gate or criterion defect is not a
pass. The fixed inventory is published with the source repository's
`completion records
<https://github.com/netresearch/typo3_http_guard/tree/main/Build/Reports/Assessment/completion>`_.
Later remediation records add actual source bindings and evidence to each
ID; they do not overwrite the frozen inventory or catalogs.
The unchanged inventory file in those completion records has SHA-256
:literal:`fd2b0df7c7b532269929804a39441b55dfeb5272a40231e8b06ef7d613e9f767`.
The separate :file:`reconciliation.json` records all 940 IDs exactly once,
including original outcomes, current raw results and qualified judgments.
The earlier :literal:`fd7f6d8` working snapshot records 684 passes, 61
failures and ten skips before later hook, Functional and license corrections.
The complete pre-publication :literal:`c823234` snapshot freezes 850 files:
755 eligible checks give **690 passes, 55 failures and ten skips**, with
AH-37 excluded. Literal regex results remain distinct from source review,
criterion calibration and applicability; they do not imply 940 passed items.
Later checksum guard and baseline changes have separate current validation;
generated report/documentation bytes also lie outside this frozen run.

.. _reconciliation-calibration:

Criterion and producer limits
=============================

Calibration preserves an actual requirement while checking the real surface:

* AH-04/AH-11's hardcoded :file:`docs/` paths miss the valid TYPO3 manual and
  ADRs under :file:`Documentation/`. Creating a second manual is unnecessary.
* PHP strict-types conditions are checked as PHP syntax; legal whitespace
  is not a defect. Downloaded research HTML is not an active Fluid template.
* The existing explicit PHPStan profiles and shared includes are examined
  instead of inferring absence from a canonical filename regex.
* Organization templates are checked at their real lowercase root paths.
  A 404 at a guessed uppercase path does not establish absence.
* Factory composition and genuine Core bootstrap proof are distinguished
  from generic service auto-discovery. Absent database, TCA, frontend and
  authentication surfaces retain their applicability decisions.
* Coverage uploader configuration, successful upload and a valid public
  report are three different observations. A broad presence regex does not
  prove that Codecov processed measured lines.

TD-49 was judged incorrectly: :file:`Documentation/Decisions/` is not an accepted checker path.
That earlier rationale in the frozen inventory and reconciliation is withdrawn. The canonical
:ref:`ADR index <adr-index>` now retains fourteen historical proposals and four accepted current
records. A separately bound actual TD-49 check passes; fresh AH-40/ER-45 reviews cover the current
factory, DNS and native-attempt decisions. :file:`reconciliation.json` records this correction under
:literal:`post_snapshot_adr_correction`; the raw 755 results and earlier 850/916 quality and 263-input
Native captures remain unchanged. Final rendering and package qualification have separate receipts.

.. _reconciliation-documentation:

Documentation and external evidence
===================================

.. list-table:: Documentation-owned checkpoint dispositions
    :header-rows: 1

    * - IDs
      - Change and evidence
      - Current disposition
    * - GH-11, ER-22
      - README links the real main CI badge and workflow.
      - Implemented; the badge reports its current source-specific status.
    * - GH-10, TT-43, ER-23
      - README links the real Codecov project and badge.
      - Main :literal:`27a958a` is complete: 82.06% measured line coverage.
    * - ER-04
      - Public Scorecard report and linked badge.
      - Verified report: 7.5 at :literal:`475ef55` on 9 October 2026.
    * - ER-06
      - Actual v0.1.1 archive/signature verification and scope.
      - Verified release evidence; no SLSA level 3 claim.
    * - ER-05, ER-24
      - Best Practices/Baseline project searches.
      - Registration prepared; authenticated browser access unavailable.
        No project ID or attained badge level claimed.
    * - TD-21
      - README, security policy and EN/DE manuals agree on publication
        and user-authorized alpha acceptance.
      - Implemented; no human review or pilot completion invented.
    * - TD-26
      - Verification split into navigable snapshots/history and DE pages.
      - Implemented using the roughly 250-line local guideline.
    * - GH-15, GH-16
      - Ordered CI/security/standards/TER rows and explicit contribution
        and development sections with contributor credits.
      - Implemented for the verified badges; external badge gaps explicit.

NB-20 is verified against the live repository metadata: its description
ends with :literal:`- by Netresearch`; the metadata update has its own receipt.

The corrected public Codecov report on 9 October 2026 is bound to main
:literal:`27a958a8e7549a9154deb11a1c85bef29c568fa2` and state
:literal:`complete`: **63 files, 2,609 measured lines, 2,141 hits and
468 misses, giving 82.06% line coverage**. It replaces the earlier
service-processing error. The API's :literal:`branches=0` does not establish
branch coverage or an absence of branches in the software. This report is
separate from the frozen local 80.80% baseline. Consult the
`actual public project <https://app.codecov.io/gh/netresearch/typo3_http_guard>`_.

The `Scorecard API
<https://api.securityscorecards.dev/projects/github.com/netresearch/typo3_http_guard>`_ is bound to its
stated source and date. Workflow success does not establish a particular score or a Best
Practices/Baseline level. The supported exact repository-URL and project-name searches return an empty
list. Registration metadata and evidence-backed answers are prepared, but authenticated registration
access is unavailable. No project ID or saved live state has been verified. This external access gap is
independent of the user's available alpha authorization.

.. _reconciliation-current-validation:

Remediation and fresh execution
==============================

The remediation installs the real staged-secret/commit hooks under
:file:`Build/`, imports the shared Makefile targets and routes genuine Core
entries through :file:`Tests/Functional/`. Typed configuration, level 10,
style/Rector, architecture and mutation have separate gates. Eight workflow
controls cover added, modified and renamed direct/nested production files;
a skipped PR diff is not a mutation score.

Focused regressions exposed three additional boundary defects: bracketed
IPv4/DNS authorities, NUL bytes in resolver A/AAAA records and a lease whose
request was missing. The released minimal fixes reject each at its own
boundary with the documented controlled reason. Genuine ordinary address
and lifecycle positives remain. The earlier CIDR NUL-lexeme correction is
a separate historical fix. Offline malformed-record tests do not establish
a native DNS exploit, and ABI doubles do not prove native wire behavior.

The integrated offline suite passes **1,535 Unit tests and 7,199 assertions** on each genuine Core
13.4.36/Guzzle 7.15.5 and Core 14.3.8/Guzzle 8.2.0 graph on PHP 8.5.11. These are local Unit executions,
separate from native wire and full-source mutation qualification. Completed kernel/Core 13/14 level 10,
architecture and Rector checks are clean. Recorded style has zero changes across 143 extension/test/tool
and 53 MIT-kernel files. All 173 current Unit inputs remain byte-identical; earlier scanner-comment
deltas retain their historical AST/byte bindings. Full quality/118-control execution binds 850 working
and 21,601 vendor files; the earlier 280 scope omitted bootstrap, harness and research inputs. Public
derivatives retain raw hashes and complete JSON values, not raw bytes.

Earlier all-source scores retain their scopes: 66.07%/73.47% on 420 inputs,
86.29%/88.65% on 556 and 89.38%/91.48% on 295. The 261-input 90.46%/91.57%
run omitted both entry-point before hashes and fails the complete-input guard.

The final unchanged **263-input** run records **3,794 mutants, 90.43% MSI
and 91.54% Covered MSI**, exit 0, with no skipped or ignored mutants.
All source and 21,601 installed vendor files match their before-run hashes:
262 inputs are shared with Root; generated :file:`composer.lock` belongs
only to the snapshot. Both PHP extension entry points are included.
There are 3,424 test kills (90.2478%), 293 survivors, 46 uncovered mutants,
three errors, four syntax errors and 24 timeouts. Tool MSI credits error
and syntax cases; :literal:`--with-timeouts` does not credit timeouts.
Both 90%/90% targets pass; raw records retain exact runtime/scope bindings.

The first native initial suite passes 389 tests/3,255 assertions on its
recorded tuple; three repeats share seed :literal:`1791577989`.

The named `GitHub reference run
<https://github.com/netresearch/typo3_http_guard/actions/runs/38000922527>`_
measures **0.604912 ms p95 against 2 ms**: 500 samples after 30 warmups,
64 IPv6 addresses and 128 profiles on Ubuntu 24.04/AMD EPYC 7763/PHP 8.5.11/
Core 14.3.8/Guzzle 8.2.0. It excludes DNS, network and log-sink time. Its
merge ref is :literal:`de0807b2979360bfc5f0993a66904674214cc44d`; its
production-tree hash is
:literal:`36eafea456ddf1115b56b1e059b3e8d8992b1fabc5d9fc8a49f6244b48c6d866`.
The first native suite's caller-sink witness separately transfers 4 MiB with
less than 4 MiB extra peak memory. Neither result is assigned to later bytes.

Historical suite numbers and hashes remain bound to their sources. Final
CI, Verification and Security gates must pass for the independently reviewed
head before merging. Local success does not prove a later remote run.

.. _reconciliation-alpha:

Alpha scope and external publication
====================================

The user explicitly authorizes alpha development and merging after
independent agent review, resolved findings and green applicable checks.
There is **no additional human approval or operator-pilot gate** for this
stage. Neither a human security review nor an actual representative pilot
is recorded as completed. Both remain recommendations for production use;
they are deferred scope, not passing assessment items.

Version **0.1.1 is published on TER and Packagist**. After the original
post-publication attestation failure, the corrected verification-only run
passed against the immutable release; see :ref:`release-provenance`.

TYPO3 Intercept's recorded :literal:`main` and :literal:`0.1` deployments remain **Awaiting Approval**.
The public main manual URL returned 404 on 9 October 2026. Local warning-free EN/DE rendering and
included source manuals do not constitute hosted approval. This external publication state does not
block the authorized alpha work. No new release is implied by these documentation changes.
