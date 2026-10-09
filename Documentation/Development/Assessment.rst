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

The source repository records raw mechanical results, per-checkpoint agent
reviews, applicability decisions and measurements under
`Build/Reports/Assessment
<https://github.com/netresearch/typo3_http_guard/tree/main/Build/Reports/Assessment>`_.
These optional development records are excluded from the extension ZIP.

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

Supported PHP metadata is limited to PHP 8.2 through 8.5. Exact qualified
TYPO3 and SDK combinations continue to fail closed; the assessment does
not broaden runtime support.

Level 8 static analysis now also runs against genuine TYPO3 13.4.35 vendors
for each of the three qualified SDK tuples. The inactive Core 14 declaration
has an explicitly marked analysis shell in ``Build/``; production never
loads that shell. Core 14 and kernel checks have separate configurations.

The kernel PHPUnit configuration now treats risky tests as failures. The
container used for controlled wire fixtures is pinned by its image digest.

.. _assessment-open-work:

Remaining qualification work
============================

An independent human security review and representative operator pilot
remain release prerequisites. Historical fixture locks contain known
``enshrined/svg-sanitize`` advisories; disposable fixture installation
exceptions are not production audit exceptions. A clean release must use
audited dependency resolutions and complete the release qualification.

The fresh dependency resolution under the unchanged qualified Core and SDK
constraints still selects ``enshrined/svg-sanitize`` 0.22.0. Its strict audit
fails on the three recorded advisory IDs. The security gate retains this
failure rather than reporting the graph as clean.

The 18 killed targeted security mutants are historical qualification
evidence. They do not establish a project-wide Infection mutation score.
Signed release artifacts, provenance, registry publication and the named
CI performance reference remain future release work. Passing repository
checks alone does not establish SLSA level 3 or enterprise certification.
