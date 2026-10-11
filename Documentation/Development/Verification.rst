.. _verification-report:

===================
Verification report
===================

The records below distinguish compatibility rules, executed source snapshots
and historical qualification. Their counts remain bound to the recorded
source and dependencies. A later code change requires its own applicable
checks; the presence of a report does not prove a new execution.

See :ref:`assessment-reconciliation` for the frozen 940-checkpoint inventory,
current remediation and external service limits. The original requirement
ledger and archived executions remain unchanged.

.. _verification-integrated-local:

Integrated local checks
=======================

The integrated offline Unit suite passes **1,535 tests and 7,199 assertions**
on each genuine Core 13.4.36/Guzzle 7.15.5 and Core 14.3.8/Guzzle 8.2.0
graph on PHP 8.5.11. These local Unit runs do not constitute native wire
execution or a final full-source mutation score.

Completed local kernel and actual Core 13/14 level 10 analyses and the
architecture check report zero errors; Rector proposes no changes. The
recorded style dry run finds zero changes in **196 files**: 143 in the
extension/test/tool scope and 53 in the embedded MIT kernel scope.
All 173 current Unit PHP/configuration inputs remain byte-identical
during both runs. Earlier scanner-comment deltas retain their separately
verified identical executable AST and historical byte bindings.

The complete quality/118-control execution passes with unchanged before/end
hashes for all **850 working files and 21,601 installed vendor files**,
matching the pre-publication mechanical snapshot. The earlier 280-file
receipt omitted mutation-bootstrap, harness and research-header inputs.
Generated reports and documentation do not inherit that byte-equality claim.
Subsequent checksum guard and baseline corrections retain separate current validation;
the 850 snapshot contains the earlier guard representation.

The final all-source native run passes **90.43% MSI/91.54% Covered MSI**
over 3,794 mutants: 3,424 test kills, 293 survivors, 46 uncovered, three
errors, four syntax errors and 24 timeouts, zero skipped or ignored.
Its original 263-input before/end maps and 21,601 vendor files match exactly;
261 working inputs match the mechanical snapshot; one installed metadata
file separately matches captured vendor data and current Root bytes.
The generated lock belongs only to the measured snapshot.
Runtime is PHP 8.5.10/Core 14.3.8/Guzzle 8.2.0, default mutators and timeout 20;
unchanged targets are 90%/90%. Test kills alone are 90.2478%; tool MSI credits
errors/syntax cases and does not credit timeouts with :literal:`--with-timeouts`.
The earlier 261-input 90.46%/91.57% run omitted both PHP entry-point before
hashes and is incomplete-binding history. An interrupted 297-input attempt
has no completed score; stale report files do not supply one.

Public typed SHA-256 derivatives name their transformation, original raw
hash and their own byte hash. They reconstruct the complete original JSON
value and preserve all counts, without claiming identical original bytes.
The authoritative raw captures remain unchanged outside the active checkout.

The earlier local remediation snapshot passes **340 Unit tests and 2,125
assertions** on PHP 8.5.11. Its kernel/Core 14 level 10 and architecture
checks report zero errors; style reports zero changes in 158 files and
Rector proposes none. These recorded counts do not describe later source
and test additions or requalify the historical native executions below.

The first isolated native all-source Infection run measures 3,790 mutants,
66.07% MSI and 73.47% Covered MSI on its 420-input snapshot. A later run
measures 3,794 mutants and 86.29%/88.65% on 556 unchanged inputs. It predates
the latest reporter, DNS, lease and runner-cleanup corrections. Both fail
the unchanged 90%/90% targets. A focused passing scope does not prove a
passing full-source run.

The first native initial suite passes 389 tests/3,255 assertions; three
repeats share seed :literal:`1791577989`, rather than independent random
samples. The named GitHub policy reference measures 0.604912 ms p95 against
2 ms on its own production tree. Full counts, error/timeout semantics and
source boundaries are in :ref:`assessment-reconciliation`.

.. _verification-semantic-support:

Semantic support and recorded proof
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

The reviewed semantic-contract snapshot passes **201 tests and 2,405 assertions**
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

The subsequent `test-counter correction
<https://github.com/netresearch/typo3_http_guard/pull/3>`_ changes only the
test fixtures and assertions. No-contact checks compare cumulative TCP and
HTTP counters across all four destinations; asynchronous cleanup of an
earlier connection can change its active/closed counters without creating
new contact. Native handle and cancellation checks remain enforced.
The corrected local suite passes **201 tests and 2,447 assertions** on
PHP 8.5.10 with Guzzle 8.2.0, Promises 3.0.2 and PSR-7 3.1.0. Actual
fixture cleanup reproduces the old false failure; new TCP and reused-connection
HTTP contacts still fail the corrected comparison. All eight per-destination
counter controls reject new contact. The five-run snapshot above retains
its original source hashes and assertion counts.

The final Composer Core matrix is a separate execution against the corrected
parent ABI guard: **168 processes, 140 bootstrap checks, including expected guard denials and 156 offline
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

.. toctree::
    :maxdepth: 1

    VerificationSnapshots
    VerificationHistory
