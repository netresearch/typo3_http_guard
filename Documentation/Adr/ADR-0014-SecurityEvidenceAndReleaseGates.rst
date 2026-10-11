.. _adr-0014:

=========================================================================
ADR-0014: Qualify security through target-contact and regression evidence
=========================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-039, HG-040, HG-041, HG-044, HG-045
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0014-context:

Context
=======

A test may pass even if it observes only an exception after a request has
already been sent. DNS rebinding, redirects and shared pools are not
sufficiently demonstrated by unit mocks alone. Existing Vault ADRs already
distinguish transport-not-reached tests from real wire evidence.

Sources: :literal:`[S06, S08, S19]`.

.. _adr-0014-decision:

Decision
========

The normative corpus contains 84 test cases and maps all 45 requirements.
Every critical denial case needs a negative target-contact proof: a transport
spy first, plus instrumented hermetic target servers or network evidence for
transport invariants. The test environment must not contact real cloud
metadata services or foreign internal services.

Test layers: unit/property, library integration, genuine TYPO3 registration,
real cURL network tests, concurrency/workers and Vault
normal/OAuth/streaming/cancellation paths. Boundary cases use the same corpus.
Isolated networks and injected resolvers make answers reproducible;
qualification tests have no internet dependency.

Targeted mutations remove, for example, the pin or address check; the
corresponding tests must then fail. Release gates require an integration probe
first, then complete P0 evidence and independent security review. A new
dependency major or security fix restarts the relevant gates.

.. _adr-0014-rejected-alternatives:

Rejected alternatives
=====================

- **Coverage percentage alone:** says nothing about the claimed invariant.
- **Check exceptions alone:** may be too late.
- **Only a manually tested URL:** not reproducible and potentially dangerous.
- **Present existing ADR measurements as new evidence:** confuses a source
  with executed verification.

.. _adr-0014-consequences:

Consequences
============

Qualification can be traced to concrete guarantees. A test harness and
multiple runtime combinations take effort, but prevent unnoticed regressions.
This original documentation package alone does not yet satisfy those product
gates.

.. _adr-0014-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T001-T084; especially T029-T033, T043-T048, T053-T061, T074-T080`.
Details and expected results are in `Original verification specification`_.
These tests were specified, not executed as part of the original documentation
delivery.

.. _adr-0014-reassessment-trigger:

Reasons to reconsider
=====================

New attack paths, transport options or consumers expand the corpus before
approval. An irreproducible security promise must be narrowed rather than
maintained in marketing.

.. _adr-0014-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0014-sicherheitsnachweis-und-release-gates.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
