.. _development-assessment:

=====================
Repository assessment
=====================

The repository is assessed using Netresearch's automated assessment,
TYPO3 conformance and enterprise readiness skills. The initial assessment
is bound to commit ``7a3a39bbaa763eb876ff4c9cdc743b689c557590``.
It evaluates the source and repository settings separately from the earlier
HTTP transport qualification. The counts on this page preserve historical
snapshots. :ref:`assessment-reconciliation` describes the frozen 940-ID
inventory and subsequent remediation without replacing those raw results.

.. _assessment-scope:

Scope and evidence
==================

Mechanical checkpoints and actual model reviews are recorded separately.
A failed generic checkpoint is reviewed for applicability before it becomes
a project finding. Skipped or unsupported checks are not successful checks.

.. list-table:: Initial specific checkpoint results
    :header-rows: 1

    * - Skill
      - Pass
      - Fail
      - Skip
    * - TYPO3 conformance
      - 81
      - 16
      - 1
    * - Enterprise readiness
      - 7
      - 23
      - 7

These are raw results on the initial revision, before the corrections. They
are not an adjusted compliance score. Examples of calibrated false positives
include valid whitespace in strict-types declarations, downloaded research
HTML mistaken for active Fluid templates, and factory-composed internal
interfaces excluded from TYPO3 service discovery. UI, TCA and database checks
do not apply to this extension's current surfaces.

The automated assessment selects eleven relevant skill catalogs. Their
755 mechanical checkpoints produce 548 passes, 164 failures and 43 skips,
with no blocked checks. The security catalog alone contains 376 checkpoints;
its native-filesystem run verifies all 1,912 frozen source hashes before
execution and produces 364 passes, seven failures and five skips.

The 180 individual agent-review checkpoints produce 65 passes, 28 findings,
81 applicability or scope skips, three future-release deferrals and three
requests for further evidence. They are agent reviews, not independent
human security approval. Four executable checks misplaced in a catalog's
model-review section are executed separately and all pass.

The source repository records raw mechanical results, per-checkpoint agent
reviews, applicability decisions and measurements under
`Build/Reports/Assessment
<https://github.com/netresearch/typo3_http_guard/tree/main/Build/Reports/Assessment>`_.
These optional development records are excluded from the extension ZIP.

A subsequent full conformance and enterprise checkpoint run records 109
passes, 25 failures and one skip across 135 checks. Its newly exposed TER
title-prefix inconsistency is corrected and the affected checkpoint passes
on a targeted rerun. The merged recorded result is 110 passes, 24 failures
and one skip; the unchanged raw runs and snapshot boundaries are preserved
under ``Build/Reports/Assessment/post/``.

The extension remains a published alpha. The user-authorized alpha scope has
no additional human approval or operator-pilot prerequisite. Neither activity
has been recorded as completed; the assessment records agent reviews and
technical measurements with their actual limits.

.. _assessment-runtime-evidence:

Measured runtime coverage
=========================

The frozen baseline passes 145 tests with 2,253 assertions in the combined
Unit and kernel integration PHPUnit suite. On PHP 8.5 with Xdebug, measured
line coverage is 80.80% (1,873 of 2,318 executable lines) and method coverage
is 48.50% (97 of 200 methods).

The Unit suite alone passes 96 tests with 1,173 assertions and measures
57.25% line coverage. The separately executed TYPO3 bootstrap, CLI and
classic installation matrices do not contribute to those coverage numbers.
Their historical source binding is documented in :ref:`verification-report`.

The corrected public Codecov result belongs to main :literal:`27a958a`:
82.06% line coverage across 2,609 measured lines in 63 files, with 2,141
hits and 468 misses. It is distinct from the frozen local baseline and
does not establish branch coverage. The integrated offline Unit suite
passes **1,535 tests and 7,199 assertions** on each actual Core 13/Guzzle 7
and Core 14/Guzzle 8 graph on PHP 8.5.11. The earlier local remediation
snapshot passes 340 Unit tests and 2,125 assertions. Later source changes,
quality executions and mutation scopes retain separate bindings under
:ref:`assessment-reconciliation`.

Completed local kernel and actual Core 13/14 level 10 analyses and the
architecture check report zero errors; Rector proposes no changes. The
recorded style dry run finds zero changes in **196 files**: 143 in the
extension/test/tool scope and 53 in the embedded MIT kernel scope.
All 173 current Unit PHP/configuration inputs remain byte-identical
during both runs. Earlier scanner-comment deltas retain their separately
verified identical executable AST and historical byte bindings.

.. _assessment-repository-corrections:

Repository corrections
======================

The English manual is the primary documentation. Its German translation
lives in ``Documentation/Localization.de_DE/``. The previous ``docs/``
directory is consolidated into the manual.

README, contributor instructions, security policy and other active project
prose use English. Repository labels and topics follow the corresponding
Netresearch TYPO3 extension conventions. Source headers retain the existing
GPL-2.0-or-later extension and MIT embedded-kernel licensing.

Production metadata uses PHP :literal:`^8.2` and Core
:literal:`^13.4.36 || ^14.3.8`. Compatible future patches and minors remain
usable without a new extension release, subject to the actual Core/SDK API
and capability checks. Historical PHP 8.2–8.5 coverage does not claim future
PHP executions. See :ref:`verification-semantic-support` for the separate
semantic-contract proof.

GitHub enforces signed commits and the CI, Verification and Security gates,
including for administrators. The ``t3x-pull-request`` ruleset follows the
Netresearch TYPO3 extension convention: contributors need one approval and
reviews are dismissed on new commits; administrators, Renovate and Dependabot
have a bypass for that rule only through pull requests. This does not bypass
the required checks or signed commits.

The shared Netresearch PR quality workflow provides automated approval for
non-draft, same-repository PRs whose author has write or administrator access.
Fork PRs receive no automated approval. This is an automated repository
approval, not evidence of human security review. Authorized alpha merging
still requires resolved findings and green applicable checks.
Auto-merge is enabled, and the default Actions token remains read-only with
explicit per-job scopes.
New CI workflows require actual execution before their presence can be treated
as operational evidence.

The initial level 8 analysis also ran against genuine TYPO3 13.4.35 vendors
for each of the three qualified SDK tuples. The inactive Core 14 declaration
has an explicitly marked analysis shell in ``Build/``; production never
loads that shell. Core 14 and kernel checks have separate configurations.

The kernel PHPUnit configuration now treats risky tests as failures. The
container used for controlled wire fixtures is pinned by its image digest.

Development tools use :literal:`phpunit/phpunit:^11.5`,
:literal:`phpstan/phpstan:^2.3` and the CI meta-package :literal:`^1.12`.
The historical PHPStan 2.3.1 level 8 review initially reports five missing
HandlerStack generics and a redundant cleanup-callable diagnostic. Precise
annotations preserve the capability check; the analysis-only
:file:`Build/PhpStan/HandlerStack.stub` supplies Guzzle 7's missing template
while retaining installed SDK methods/properties. It never runs in production.
The refreshed four genuine Core 13.4.36/14.3.8 integration profiles and three
kernel SDK profiles pass with zero errors, without a baseline or suppression.
The initial six diagnostics retain their frozen source and outcomes.

.. _assessment-review-loop:

Recorded review corrections
===========================

The patched Core 13.4.36 and 14.3.8 dependency graphs select SVG sanitizer
1.0.0 and report zero vulnerability advisories across four exact full Core
and SDK combinations. Active fixture preparation and CI no longer contain
advisory exceptions. Core 13's upstream abandoned annotations warning
remains visible under the explicit reporting policy described in
:ref:`dependency-report-current`.

The prior review snapshot at commit :literal:`e69ddad` passed all four
Composer fixtures through actual bootstrap, mode, CLI
and wire matrix: 168 processes, 140 bootstrap checks, including expected guard denials and 156 offline
TCP/HTTP no-contact witnesses. Both current classic archives separately
pass 84 processes, 70 bootstrap checks and 78 offline witnesses. Real Core
activation persists the extension's PackageStates and generates its
class-loading cache. All three exact SDK tuples pass the refreshed combined
Unit/native suite with 145 tests and 2,253 assertions each. These counts
belong to that snapshot before the semantic-contract change. See
:ref:`verification-current-core` for that proof and
:ref:`verification-semantic-support` for current source-bound results.
Historical coverage figures and checkpoint totals are not assigned to
these new runs.

The semantic-contract snapshot passes five complete Unit/native runs over
four distinct SDK tuples, including both minima: **201 tests/2,405 assertions**
each on pinned PHP 8.5.10/PHPUnit 11.5.57. Each includes 152 Unit tests/1,329
assertions and 49 integration tests/1,076 assertions; all 108 bound inputs
remain unchanged. The separate Composer Core matrix passes 168 processes,
140 bootstrap checks, including expected guard denials and 156 offline witnesses. Curated Core 13/14 static
profiles have zero errors; the parent ABI regression passes 19 tests/31
assertions and the named security-floor, future-minor and warning-restoration
mutants fail their controls. See :ref:`verification-semantic-support` for
exact versions and source binding; classic evidence retains its own snapshot.

A subsequent test-only correction compares cumulative TCP and HTTP contacts
without treating asynchronous connection cleanup as a new request. Its
local suite passes **201 tests and 2,447 assertions**; native-handle and
cancellation checks remain enforced. The five-run evidence above keeps
its original test hashes and counts. See
:ref:`verification-semantic-support` for the correction and its controls.

.. _assessment-open-work:

Remaining qualification work
============================

The user has deferred independent human security review and a representative
operator pilot from alpha acceptance. They remain uncompleted and recommended
when assessing production use. The original Core resolutions selected
``enshrined/svg-sanitize`` 0.22.0 and failed on three advisory IDs. The
initial GitHub inventory counted 36 medium alerts for those same IDs
repeated in twelve historical fixture lock files. The records now remain
inside explicit archives with byte-preserving mappings. Current patched
Core resolution is reported separately above; an archived alert count is
not the current production dependency audit.

The 18 killed targeted security mutants are historical qualification
evidence. They do not establish a project-wide Infection mutation score.
The frozen inventory identified missing style, max-level analysis,
architecture, fuzz and general Infection gates. Current implementation and
its newly measured results are reconciled separately; syntax linting alone
is not a style check, and targeted mutants are not a general MSI.
The release workflow verifies artifact signatures, provenance and registry
publication separately. Consult the
`release status <https://github.com/netresearch/typo3_http_guard/releases>`_
for the actual publication outcomes; registering a documentation webhook
does not by itself prove that TYPO3 has approved and rendered the manual.
The named GitHub policy reference measures 0.604912 ms p95 against 2 ms.
The first completed isolated native all-source measurement is 66.07% MSI
and 73.47% Covered MSI across 3,790 mutants. A later 556-input snapshot
measures 86.29%/88.65% across 3,794 mutants; it predates the latest reporter,
DNS, lease and runner-cleanup corrections. Both remain historical failures. The final 263-input run passes at
**90.43% MSI/91.54% Covered MSI**, exit 0 with no skipped/ignored mutants
and 21,601 unchanged vendor files. Incomplete prior 261 history is qualified
under :ref:`verification-integrated-local`. The
published 940-ID reconciliation preserves
literal outcomes and separately qualified judgments. OpenSSF registration
is prepared but authenticated browser access
is unavailable. Current dispositions are recorded in
:ref:`assessment-reconciliation`. Passing repository checks alone does not
establish SLSA level 3 or enterprise certification.
