.. _adr-0002:

============================================================================
ADR-0002: A shared library instead of a Vault dependency for every extension
============================================================================

:Status: Superseded by :ref:`ADR-0015 <adr-0015>` (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-006, HG-036, HG-037, HG-041, HG-045
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    :ref:`adr-0015` supersedes the package split. The original reasons for
    separating security and integration responsibilities remain historical
    context; the original decision below is not silently rewritten.

.. _adr-0002-context:

Context
=======

nr-vault already has target validation, DNS pinning and its own handler stack.
It also performs tasks outside general network protection: secret access,
credential injection, audit, OAuth and specialized streaming/cancellation
APIs.

Sources: :literal:`[S04-S08, S16]`.

.. _adr-0002-decision:

Decision
========

Proposed split: :literal:`netresearch/http-guard` as a standalone PHP library
and :literal:`netresearch/nr-http-guard` as the TYPO3 integration. nr-vault
consumes the library through its own adapter. Configuration, resolver, clock
and reporter are injected into the library; it reads no TYPO3 globals and
knows no Vault database.

Security mechanisms and their regressions are extracted, rather than accepting
every historical exception without review. Vault retains its
credential-bearing interfaces and domain responsibility. The library package
offers no secret export.

When existing Vault code is adopted, its copyright and SPDX notices are
retained. As a compatible project proposal, both new packages use
:literal:`GPL-2.0-or-later`; publication under a different license requires
separate prior clarification of rights. This ADR grants no new rights.

.. _adr-0002-rejected-alternatives:

Rejected alternatives
=====================

- **Every extension depends on nr-vault:** unnecessary domain coupling.
- **A third copy of the same methods:** security fixes drift between copies.
- **Replace Vault's entire client:** threatens established
  authentication/streaming semantics.
- **Introduce a standalone library later:** entrenches incorrect dependencies
  again.

.. _adr-0002-consequences:

Consequences
============

A security fix reaches every integrated path through the same corpus. Two new
package surfaces and coordinated releases are required. Abstractions remain
limited to the two actual integrations; this is not a general HTTP framework.

.. _adr-0002-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T007, T069, T070, T072, T076, T080`. Details and expected results
are in `Original verification specification`_. These tests were specified, not
executed as part of the original documentation delivery.

.. _adr-0002-reassessment-trigger:

Reasons to reconsider
=====================

A third consumer may justify additional adapters. Extending the library with
credential management requires a new decision and is not implicitly permitted.

.. _adr-0002-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0002-bibliothek-und-integrationen.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
