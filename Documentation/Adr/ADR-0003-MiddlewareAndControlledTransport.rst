.. _adr-0003:

=======================================================================
ADR-0003: Two middleware boundaries and a controlled terminal transport
=======================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-001, HG-002, HG-009, HG-019, HG-021, HG-023
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

    :ref:`adr-0016` records the additional public RequestFactory boundary in
    the implemented adapter. The historical rejected alternative below
    concerns the internal client factory.

.. _adr-0003-context:

Context
=======

Core installs its own middleware handlers after Guzzle's defaults and the
optional Core allowlist. An ordinary precheck before an opaque
:literal:`$next` does not guarantee which transport runs afterwards. Later
rewriting of a request origin or response Location can also invalidate an
earlier check.

Sources: :literal:`[S01, S05, S12, S20]`.

.. _adr-0003-decision:

Decision
========

The TYPO3 adapter registers :literal:`nr/http-guard-boundary` as the first and
:literal:`nr/http-guard-terminal` as the last custom middleware entry.
Existing custom middleware remains between them. Boundary captures the checked
input origin; Terminal verifies the final target and, in Enforce mode, starts
a controlled transfer instead of the automatically selected leaf handler.

Origin changes by intervening middleware are denied. Boundary checks the final
response after custom response middleware and before Guzzle redirect
processing. This also covers a Location changed afterwards. Ordinary redirects
pass through the stack again.

Order and uniqueness are invariants. An existing :literal:`HandlerStack` as
global configuration or an entry after Terminal is not silently compatible.
AP-01 must prove the specific registration path in the real bootstrap of both
TYPO3 versions; the design does not claim an appropriate, unverified Core
event.

.. _adr-0003-rejected-alternatives:

Rejected alternatives
=====================

- **Precheck middleware alone:** leaves transport uncontrolled.
- **Decorate/Xclass the internal Core factory:** unnecessary dependency on an
  internal API.
- **Replace the entire client:** loses existing middleware semantics.
- **A terminal guard without Boundary:** cannot control redirect responses
  changed late.

.. _adr-0003-consequences:

Consequences
============

The documented extension point remains the entry point; the transport
guarantee is stronger than a host filter. The solution deliberately is not
fully transparent: fixed order, no arbitrary leaf handler and bootstrap
compatibility that must be demonstrated. This is the largest technical
approval gate in the historical design.

.. _adr-0003-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T001, T002, T003, T004, T048, T083`. Details and expected results
are in `Original verification specification`_. These tests were specified, not
executed as part of the original documentation delivery.

.. _adr-0003-reassessment-trigger:

Reasons to reconsider
=====================

If the integration probe fails, this ADR is replaced. Uncontrolled
:literal:`$next` delegation must not be approved as an equivalent alternative.

.. _adr-0003-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0003-middleware-und-kontrollierter-transport.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
