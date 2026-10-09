.. _development-assessment:

=====================
Repository assessment
=====================

The repository is assessed using Netresearch's automated assessment,
TYPO3 conformance and enterprise readiness skills. The initial assessment
is bound to commit ``7a3a39bbaa763eb876ff4c9cdc743b689c557590``.
It evaluates the source and repository settings separately from the earlier
HTTP transport qualification.

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

The extension remains an alpha. This assessment does not replace an
independent security review or an operator pilot with representative
outbound requests.

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

Supported PHP metadata is limited to PHP 8.2 through 8.5. Exact TYPO3 and
SDK combinations continue to fail closed. The review loop replaces the
previous Core targets with the patched 13.4.36 and 14.3.8 releases and
repeats the relevant runtime qualification; it does not widen the Core range.

GitHub enforces signed commits and the existing required review also applies
to administrators. New CI workflows require actual execution before their
presence can be treated as operational evidence.

The initial level 8 analysis also ran against genuine TYPO3 13.4.35 vendors
for each of the three qualified SDK tuples. The inactive Core 14 declaration
has an explicitly marked analysis shell in ``Build/``; production never
loads that shell. Core 14 and kernel checks have separate configurations.

The kernel PHPUnit configuration now treats risky tests as failures. The
container used for controlled wire fixtures is pinned by its image digest.

Development tools are pinned to the actually executed PHPUnit 11.5.57 and
PHPStan 2.3.1. The initial Guzzle 8 kernel analysis with PHPStan 2.3.1
reports five missing ``HandlerStack`` generic annotations and one redundant
native-cleanup callable-check diagnostic. Those six findings were open at
that recorded snapshot. The subsequent review loop adds precise generic
annotations and preserves the native-cleanup capability check. The
analysis-only :file:`Build/PhpStan/HandlerStack.stub` supplies
Guzzle 7's missing class-level template and preserves its final contract;
method and property signatures remain those of the installed SDK. It is
excluded from the extension ZIP and never substitutes a runtime handler.
Fresh kernel PHPStan 2.3.1 level 8 checks pass for all three SDK tuples
without suppressions or a baseline. Regression results are recorded
separately from the initial assessment.

The refreshed analyses against Core 13.4.36 and 14.3.8 pass all four
integration configurations and all three kernel SDK configurations with
PHPStan 2.3.1 at level 8 and zero errors. These are new executions; the
earlier six-diagnostic result remains in the frozen assessment records.

.. _assessment-review-loop:

Current review corrections
==========================

The patched Core 13.4.36 and 14.3.8 dependency graphs select SVG sanitizer
1.0.0 and report zero vulnerability advisories across four exact full Core
and SDK combinations. Active fixture preparation and CI no longer contain
advisory exceptions. Core 13's upstream abandoned annotations warning
remains visible under the explicit reporting policy described in
:ref:`dependency-report-current`.

All four current Composer fixtures pass their actual bootstrap, mode, CLI
and wire matrix: 168 processes, 140 wire assertions and 156 offline
TCP/HTTP no-contact witnesses. Both current classic archives separately
pass 84 processes, 70 wire assertions and 78 offline witnesses. Real Core
activation persists the extension's PackageStates and generates its
class-loading cache. All three exact SDK tuples pass the refreshed combined
Unit/native suite with 145 tests and 2,253 assertions each. See
:ref:`verification-current-core` for the separate source-bound records.
Historical coverage figures and checkpoint totals are not assigned to
these new runs.

.. _assessment-open-work:

Remaining qualification work
============================

An independent human security review and representative operator pilot
remain release prerequisites. The original Core resolutions selected
``enshrined/svg-sanitize`` 0.22.0 and failed on three advisory IDs. The
initial GitHub inventory counted 36 medium alerts for those same IDs
repeated in twelve historical fixture lock files. The records now remain
inside explicit archives with byte-preserving mappings. Current patched
Core resolution is reported separately above; an archived alert count is
not the current production dependency audit.

The 18 killed targeted security mutants are historical qualification
evidence. They do not establish a project-wide Infection mutation score.
The existing PHP formatting also leaves coding-style recommendations open;
syntax linting is not a full PSR-12 style check.
Signed release artifacts, provenance, registry publication and the named
CI performance reference remain future release work. Passing repository
checks alone does not establish SLSA level 3 or enterprise certification.
