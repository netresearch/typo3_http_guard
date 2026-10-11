.. _adr-0011:

===============================================================================
ADR-0011: Retain Vault streaming without falling back to the PHP stream handler
===============================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-028, HG-029, HG-030, HG-036, HG-037
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

    The proposed Vault adapter below is historical context. Its foreign
    reference overlay is retained outside this extension; this ADR does not
    establish a current Vault integration or deployment.

.. _adr-0011-context:

Context
=======

Vault's ADR-039 describes streaming through a pinned curl-multi transfer whose
progress is driven while the response body is read. :literal:`stream=true`
would instead select the PHP stream handler and lose the pin. The
credential-bearing API deliberately encapsulates transport, promise and
secrets.

Sources: :literal:`[S05, S08]`.

.. _adr-0011-decision:

Decision
========

In v1, global middleware supports ordinary buffered sends and explicitly
rejects Guzzle's :literal:`stream=true`. This does not exclude Vault's
separate streaming API. Its adapter uses the same policy/ConnectionPlan logic,
but receives an internally controlled transfer lease for ongoing progress and
cancellation.

A single owner is responsible for the tick loop, settlement and release.
Cancellation before start prevents the transfer; cancellation in flight closes
the active socket. Body close, errors and aborted consumption release
resources idempotently. Errors after partial responses are not presented as
complete EOF success.

Vault retains credential injection, prior secret permissions, audit, buffer
limits, idle/total time semantics and the distinction between proxy and origin
responses in its existing regressions. The new v1 proxy boundary is treated as
an explicit compatibility change rather than hidden. Its public API still
exports no raw security options.

.. _adr-0011-rejected-alternatives:

Rejected alternatives
=====================

- **Remove streaming entirely:** unnecessary product loss.
- **Accept** :literal:`stream=true`: selects the wrong transport.
- **Copy the streaming mechanism anew:** avoidable errors around EOF, buffer
  limits and cancellation.
- **Expose a raw client:** expands the credential-bearing surface.

.. _adr-0011-consequences:

Consequences
============

The demanding Vault feature is retained. Migration requires real
streaming/cancellation tests and is not complete merely by replacing a
factory. Long streams remain resource-bearing transfers that must be ended
explicitly.

.. _adr-0011-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T058, T059, T060, T061, T069, T070, T071, T072`. Details and
expected results are in `Original verification specification`_. These tests
were specified, not executed as part of the original documentation delivery.

.. _adr-0011-reassessment-trigger:

Reasons to reconsider
=====================

Global incremental streaming is a later feature. It must satisfy the same pin,
lifecycle and error obligations, rather than merely enabling a different
Guzzle switch.

.. _adr-0011-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0011-vault-streaming-und-cancellation.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
