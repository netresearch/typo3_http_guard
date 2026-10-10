.. _adr-0009:

====================================================================
ADR-0009: One canonical target parser and a versioned address corpus
====================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-007, HG-008, HG-009, HG-010, HG-011, HG-012
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0009-context:

Context
=======

Parsers interpret noncanonical numeric addresses differently. The Host header
and URI may disagree. A coarse private-IP filter does not cover every local or
special address class. IANA maintains separate IPv4/IPv6 special-purpose
registries.

Sources: :literal:`[S04, S05, S10, S11]`.

.. _adr-0009-decision:

Decision
========

The guard normalizes once, then uses a typed Target and applies the same
authority to transport. Absolute HTTP/HTTPS URLs are mandatory; userinfo,
fragments, control characters, zone IDs, host percent encoding and
noncanonical numeric forms are rejected. After normalization, HTTP Host and
URI authority must agree. Unicode hostnames are not implicitly converted in
v1; ASCII IDNA names already converted canonically are permitted.

IP and CIDR checks use binary representations for both families. IPv4-mapped
IPv6 is evaluated against the embedded IPv4 policy. Tunneling/translation
ranges are conservatively blocked. The versioned corpus names private ranges,
loopback, link-local, metadata relevance, multicast, documentation/benchmark
and other special ranges.

The v1 public policy is deliberately more conservative than merely accepting
the positive Globally-Reachable marking of individual special addresses.
Operator deny can additionally block nominally public ranges routed
internally. Registry updates are versioned, never downloaded live for every
request.

.. _adr-0009-rejected-alternatives:

Rejected alternatives
=====================

- **Regular expressions alone:** unsuitable for comprehensive IP/CIDR
  decisions.
- **PHP filter flags alone:** implicitly couples policy to their particular
  runtime semantics.
- **Private IPv4 without IPv6:** leaves a second address family open.
- **Accept every parser correction:** increases interpretation differences.

.. _adr-0009-consequences:

Consequences
============

There is one testable decision path and traceable dataset versions. Some
legitimate special addresses and Unicode inputs are restricted; callers must
supply canonical targets. A public IP still does not mean "a server whose
content is trustworthy".

.. _adr-0009-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T008, T009, T010, T011, T012, T013, T014, T015, T016, T017, T018, T019, T020, T021`.
Details and expected results are in `Original verification specification`_.
These tests were specified, not executed as part of the original documentation
delivery.

.. _adr-0009-reassessment-trigger:

Reasons to reconsider
=====================

Relaxing a blocked address class or parser form requires a corpus change,
justification and boundary tests. New IANA entries are treated like
security-relevant dependency changes.

.. _adr-0009-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0009-normalisierung-und-adressklassen.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
