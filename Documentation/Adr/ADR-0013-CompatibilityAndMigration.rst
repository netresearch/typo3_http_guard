.. _adr-0013:

===================================================================================
ADR-0013: Explicit migration instead of silently reinterpreting existing allowlists
===================================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-005, HG-036, HG-037, HG-038, HG-043, HG-045
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0013-context:

Context
=======

Vault uses a separate stack, permits exact flat host entries as an exception
and lists ext-curl only as a suggestion. The verified TYPO3 Core version uses
nested context allowlists and permits Guzzle 7 and 8. Naive extraction can
therefore change protection boundaries and compatibility simultaneously.

Sources: :literal:`[S01, S04-S06, S13, S16]`.

.. _adr-0013-decision:

Decision
========

The shared corpus and library are implemented first, then Core integration and
an explicit Vault adapter. Installing the global extension never automatically
constitutes Vault migration. OAuth token and resource legs receive separate
authorization and pins.

A legacy report captures existing configuration but creates no active internal
permission. Operators must deliberately add scheme, port, CIDRs, methods,
purpose, responsibility and expiry. Empty DNS answers are no longer
legitimized by name permission; static mappings replace necessary hosts/NSS
special cases.

Version changes and release notes identify the new cURL requirement,
proxy/stream boundaries and stricter exceptions. Removing existing legacy
behavior is explicitly versioned. The library has no hidden legacy fail-open
switch. Supported PHP/TYPO3/Guzzle/PSR combinations are demonstrated by
resolved locks and actual CI.

.. _adr-0013-rejected-alternatives:

Rejected alternatives
=====================

- **Automatically convert old lists:** important permission dimensions are
  missing.
- **Declare global middleware protects Vault:** technically incorrect.
- **Silently combine a security fix with architectural migration:**
  complicates rollback and review.
- **Promise every major combination Composer permits:** does not replace a
  transport test.

.. _adr-0013-consequences:

Consequences
============

Migration is traceable and supports rollback, but requires configuration.
Legacy and new paths may become available at different times; diagnostics must
clearly show which is active. Secret/audit regressions are separate approval
criteria in the original proposal.

.. _adr-0013-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T039, T041, T042, T069, T070, T071, T072, T073, T080, T082`. Details
and expected results are in `Original verification specification`_. These
tests were specified, not executed as part of the original documentation
delivery.

.. _adr-0013-reassessment-trigger:

Reasons to reconsider
=====================

New Core/Guzzle majors or changes to the global extension point require
integration qualification again. A maintained legacy branch is not advertised
as equivalent protection.

.. _adr-0013-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0013-kompatibilitaet-und-migration.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
