.. _adr-0004:

======================================================================
ADR-0004: Enforce by default, Observe only as a visible migration mode
======================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-003, HG-004, HG-021, HG-033, HG-034, HG-043
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0004-context:

Context
=======

A newly installed security extension creates an assumption of protection.
Silent fallback without cURL or with invalid policy would violate that
assumption. An existing project also needs controlled inventory of its
internal connections. Vault currently deliberately provides weaker behavior
without cURL.

Sources: :literal:`[S04-S06, S16]`.

.. _adr-0004-decision:

Decision
========

New installation: :literal:`enforce`, with no internal grants. Missing
optional configuration uses safe defaults; invalid existing configuration
fails. Unsupported transport conditions block before contact with the target
or proxy.

:literal:`observe` is an explicit operator decision. Where verifiable, it
records :literal:`would_deny` or :literal:`unverifiable`, but delegates to the
previous transport and must not be considered protected. A diagnostic problem
must not appear there as a successful security check. Existing Core/Vault
controls remain untouched.

:literal:`disabled` is transparent and visible. There is no automatic switch
from Enforce to Observe or Disabled. Rollback and exceptions require changes
to trusted project configuration. No switch may come from an HTTP parameter or
a remote response header.

.. _adr-0004-rejected-alternatives:

Rejected alternatives
=====================

- **Observe as the installation default:** does not initially provide the
  expected protection.
- **Warn instead of blocking transport gaps:** makes loss of the invariant
  difficult to recognize.
- **Fail open on configuration errors:** a typo would expand permissions.

.. _adr-0004-consequences:

Consequences
============

Failures are unambiguous; existing installations may fail after activation
until legitimate internal accesses are recorded. Rollout needs an inventory,
diagnostics and deliberate authorization. Observe telemetry does not qualify
the Enforce transport.

.. _adr-0004-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T005, T006, T042, T065, T066, T067`. Details and expected results
are in `Original verification specification`_. These tests were specified, not
executed as part of the original documentation delivery.

.. _adr-0004-reassessment-trigger:

Reasons to reconsider
=====================

Changing the installation default is a product-wide security/compatibility
decision. Automatic downgrading remains prohibited regardless.

.. _adr-0004-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0004-fail-closed-und-betriebsmodi.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
