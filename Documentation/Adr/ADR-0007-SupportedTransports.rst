.. _adr-0007:

===============================================================
ADR-0007: Controlled cURL only; no proxies or PHP streams in v1
===============================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-005, HG-020, HG-021, HG-022, HG-028, HG-042
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0007-context:

Context
=======

PHP streams do not implement a cURL pin. With proxies, origin resolution can
happen at the proxy; a locally checked IP then does not prove its target
connection. Guzzle majors differ in permitted raw options.

Sources: :literal:`[S05, S08, S12, S13, S17]`.

.. _adr-0007-decision:

Decision
========

Enforce requires a tested cURL/curl-multi adapter. :literal:`stream=true`,
foreign handlers, raw cURL options and uncertified transport-sharing settings
are denied. cURL constants and behavior are checked for actual availability.
The functional minimum version for multi-address support alone is not an
accepted security baseline.

Version 1 supports no proxies. Explicit and actually inherited proxy
configuration is detected; neither sending through it nor silently bypassing
it is permitted. After the check passes, the controlled adapter prevents
accidental proxy inheritance. An incoming HTTP :literal:`Proxy` header is not
trusted process configuration.

The shared library contains separate, tested Guzzle 7/8 option adapters.
Options that redirect transport remain owned by the guard. CA bundles, mTLS
and approved timeouts are not replaced through raw override options.

.. _adr-0007-rejected-alternatives:

Rejected alternatives
=====================

- **Allow every Guzzle handler:** no uniform guarantee.
- **Allowlist a proxy as a private endpoint:** checks only the proxy, not its
  origin access.
- **Automatic direct fallback:** bypasses corporate routing.
- **Pass through arbitrary raw cURL flags:** opens independent target/resolver
  paths.

.. _adr-0007-consequences:

Consequences
============

The v1 scope is clear and verifiable, but excludes proxy installations. A
support error is visible rather than silently unsafe. Development must test
real version combinations, not only Composer resolution.

.. _adr-0007-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T040, T041, T042, T043, T044, T058, T080, T084`. Details and
expected results are in `Original verification specification`_. These tests
were specified, not executed as part of the original documentation delivery.

.. _adr-0007-reassessment-trigger:

Reasons to reconsider
=====================

A proxy adapter requires its own ADR, a defined trust model and evidence for
the actual origin path. PHP streams also require independently proven binding
before approval.

.. _adr-0007-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0007-unterstuetzte-transporte.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
