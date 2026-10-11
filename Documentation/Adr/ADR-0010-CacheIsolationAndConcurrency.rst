.. _adr-0010:

=================================================================================
ADR-0010: Allow address memoization; isolate authorization and connection pooling
=================================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-015, HG-025, HG-026, HG-027, HG-040, HG-044
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0010-context:

Context
=======

Concurrent requests may reach the same origin under different permissions or
DNS answers. Guzzle/cURL pooling and optional share handles may reuse DNS or
connection state. A new pin alone therefore does not sufficiently demonstrate
request-specific isolation.

Sources: :literal:`[S12, S14]`.

.. _adr-0010-decision:

Decision
========

Version 1 isolates each independent transfer lease, including its cURL multi
handle, DNS and connection state. No shared share handles, no adoption of
foreign pooled connections and no silent Alt-Svc or HSTS route changes. A
request with an internal grant must not privilege a later public request
through its connection pool.

A bounded positive DNS memo is permitted: by default at most five seconds and
32 hosts, or less when a shorter TTL is known. TTL 0 and negative answers are
not positively cached. Keys include resolver identity/configuration. Every use
classifies addresses again against current policy; a boolean allow is never
cached.

ConnectionPlans are single-use, short-lived attempt data. Deferred work is
authorized on the actual new attempt, not when scheduled. Policy changes apply
to new attempts; already running allowed transfers are not presented as
automatically revoked.

.. _adr-0010-rejected-alternatives:

Rejected alternatives
=====================

- **Shared pool plus a pin:** requires additional, complex isolation evidence.
- **Global host-allow cache:** mixes contexts.
- **No memoization at all:** safely possible, but causes avoidable duplicate
  resolution.
- **Live revocation of running transfers:** a separate runtime feature, not
  accomplished by flushing a cache.

.. _adr-0010-consequences:

Consequences
============

Parallel security decisions remain independent. v1 forgoes reuse between
independent attempts and accepts handshake costs. These costs must be measured
at high request volumes; the library does not buffer bodies additionally.

.. _adr-0010-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T038, T052, T053, T054, T055, T056, T057, T075, T079, T084`. Details
and expected results are in `Original verification specification`_. These
tests were specified, not executed as part of the original documentation
delivery.

.. _adr-0010-reassessment-trigger:

Reasons to reconsider
=====================

Pooling may be introduced only after a new ADR with a key/invalidation model,
concurrency tests and wire evidence. A performance objective alone is not
evidence.

.. _adr-0010-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0010-cache-isolation-und-parallelitaet.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
