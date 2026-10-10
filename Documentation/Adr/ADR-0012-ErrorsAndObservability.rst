.. _adr-0012:

========================================================
ADR-0012: Stable denial reasons without new secret leaks
========================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-031, HG-032, HG-033, HG-034, HG-035
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0012-context:

Context
=======

A security guard needs diagnostics, but logs can themselves expose
credentials. URLs may carry query tokens; requests contain bodies, cookies and
authentication headers. A statistics callback run after sending is also not
preventive connection control.

Sources: :literal:`[S04, S08, S15]`.

.. _adr-0012-decision:

Decision
========

The library supplies fixed reason codes with a typed exception surface.
Synchronous and promise-based calls do not differ in policy decision. Policy
errors are not disguised as ordinary network timeouts. PSR-18 recognizes them
as client errors; the included request may still be a sensitive object.

Telemetry contains mode, decision, profile/policy identifier, address class,
scheme/port, resolver source and correlation. No bodies, header values, URL
paths, queries or complete exceptions. Host display is configured separately;
pseudonymous host values use an operator HMAC key, not a publicly guessable
hash presented as anonymization. Metric labels retain low cardinality.

Logger errors must never turn deny into allow. Rate limits reduce log floods,
not denial counters. Diagnostic commands send no probe HTTP requests.
:literal:`on_stats` may report a deviation afterwards, but never replaces
plan/pin protection.

.. _adr-0012-rejected-alternatives:

Rejected alternatives
=====================

- **Log complete requests for debugging:** creates an exfiltration path.
- **Label every denial an attack:** confuses typos, DNS problems and abuse.
- **Always deny HTTP on logging failure:** unnecessary global availability
  coupling; Vault audit may separately be stricter.

.. _adr-0012-consequences:

Consequences
============

Operations can identify causes without exposing secrets. Deeper debugging
requires targeted synthetic reproduction. Other project middleware remains
responsible for its own logs; the guard cannot retrospectively sanitize its
telemetry.

.. _adr-0012-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T062, T063, T064, T065, T067, T068`. Details and expected results
are in `Original verification specification`_. These tests were specified, not
executed as part of the original documentation delivery.

.. _adr-0012-reassessment-trigger:

Reasons to reconsider
=====================

Before inclusion, new telemetry fields are checked for secret content and
cardinality. A complete request dump is not permitted as a debugging
convenience.

.. _adr-0012-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0012-fehler-und-beobachtbarkeit.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
