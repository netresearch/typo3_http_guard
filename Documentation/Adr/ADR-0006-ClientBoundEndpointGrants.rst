.. _adr-0006:

========================================================================
ADR-0006: Authorize internal access through client-bound endpoint grants
========================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-012, HG-017, HG-018, HG-019, HG-035, HG-038
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0006-context:

Context
=======

Internal ERP, search or LLM services are legitimate targets. A global host
permission would also give the user-controlled URL importer the same access.
In the existing factory code, TYPO3 contexts do not provide authorization
automatically propagated to arbitrary middleware. Vault's flat allowlist has
different semantics from Core's context list.

Sources: :literal:`[S01, S03-S05]`.

.. _adr-0006-decision:

Decision
========

A profile binds the exact scheme/host/port origin, methods, permitted CIDRs,
purpose, responsibility and expiry. The application receives an already-bound
client through trusted service wiring. A URL alone, or a profile name chosen
by the user, does not activate a grant.

Registry-owned, non-serializable grant objects are bound to the profile and
policy generation. The internal ConnectionPlan cannot be exported as a
persistent send token. A diagnostic permission from :literal:`policy-check` is
not a grant.

The Core allowlist, hard/globally configured prohibitions and endpoint profile
apply cumulatively. Operator deny takes precedence. Private networks need
narrow CIDRs; loopback additionally needs an explicit flag and host prefix.
Metadata/link-local and other hard prohibitions have no blanket allow switch.

.. _adr-0006-rejected-alternatives:

Rejected alternatives
=====================

- **Global** :literal:`allow_private=true`: too broad.
- **Automatically approve every configured hostname:** does not solve the
  confused-deputy problem.
- **Treat a string context as a secret:** copyable and provides no reliable
  binding.
- **Process-wide "current context":** error-prone with parallel requests.

.. _adr-0006-consequences:

Consequences
============

Internal integrations remain possible without elevating public fetch. They
require explicit client injection. Grants are an application architecture
rule, not hard isolation against code execution in the same PHP process.

.. _adr-0006-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T021, T034, T035, T036, T037, T038, T039, T068, T073`. Details and
expected results are in `Original verification specification`_. These tests
were specified, not executed as part of the original documentation delivery.

.. _adr-0006-reassessment-trigger:

Reasons to reconsider
=====================

A new class of internal targets or more flexible profile syntax requires a
threat-model update. Wildcards and network-range permissions for every caller
are not incidental convenience features.

.. _adr-0006-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0006-clientgebundene-endpoint-grants.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
