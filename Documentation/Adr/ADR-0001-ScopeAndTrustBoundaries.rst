.. _adr-0001:

============================================================
ADR-0001: Protect the outbound HTTP path, not a PHP firewall
============================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-001, HG-002, HG-018, HG-039, HG-042
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0001-context:

Context
=======

An untrusted URL can cause the server to access an internal target. The normal
TYPO3 HTTP stack provides a suitable common intervention point. Other PHP
clients and direct sockets also exist; a handler replaced for an individual
request can bypass middleware. Core does not provide a process sandbox.

Sources: :literal:`[S01-S03, S18]`.

.. _adr-0001-decision:

Decision
========

The product protects only documented, verified send paths: the integrated
default stack and explicitly integrated clients. The guard must deny access
before contact with a disallowed target. DNS queries to the configured
resolver are separate; the guard does not promise to send no network packets
at all.

Attackers may influence URLs, responses and DNS in their own zones. Installed
PHP code, policy files, TLS/resolver configuration and the operating system
form the trusted base. Endpoint grants prevent accidental expansion of
permissions, but do not protect against malicious code in the same process.
The guard also does not authorize individual paths or records on an allowed
server.

Documentation and diagnostics use "global" only to mean the covered TYPO3
stack. Outbound firewall rules and service authentication remain complementary
controls.

.. _adr-0001-rejected-alternatives:

Rejected alternatives
=====================

- **Process-wide network blocking in PHP:** cannot be achieved without
  controlling every network function.
- **URL syntax validation alone:** controls neither DNS nor the actual
  connection.
- **Network firewall alone:** valuable, but does not by itself provide
  separate permissions for individual application clients.

.. _adr-0001-consequences:

Consequences
============

The protection promise is measurable and not misleading. SDKs without an
integration remain a deliberate coverage gap; migrating them requires their
own adapters. Operations must inventory both levels.

.. _adr-0001-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T001, T003, T035, T074, T077`. Details and expected results are in
`Original verification specification`_. These tests were specified, not
executed as part of the original documentation delivery.

.. _adr-0001-reassessment-trigger:

Reasons to reconsider
=====================

A new integration or an expanded promise requires its own transport evidence.
A future process/network sandbox is a separate product objective.

.. _adr-0001-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0001-schutzumfang-und-vertrauensgrenzen.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
