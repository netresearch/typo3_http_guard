.. _adr-0008:

=======================================================================
ADR-0008: Check every redirect hop and bind credentials to their origin
=======================================================================

:Status: Historical proposal (original status: Proposed)
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-017, HG-023, HG-024, HG-025
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

.. _adr-0008-context:

Context
=======

An allowed initial URL can redirect to an internal target. Even exclusively
public targets may receive confidential bodies or custom authentication
headers. Generic middleware cannot reliably recognize these secrets. Guzzle
processes redirects outside custom handlers, and PSR-18 sends do not follow
automatically.

Sources: :literal:`[S01, S05, S15, S20]`.

.. _adr-0008-decision:

Decision
========

The generic client applies same-origin policy. Every followed Location is
checked after response middleware; the next attempt needs a new
ConnectionPlan. Scheme, host and effective port form the origin. HTTPS
downgrades are prohibited. A profile can disable following entirely.

A separate public-fetch client may follow cross-origin redirects, but only as
GET/HEAD without a body, cookies, client certificate, authorization or freely
supplied headers. This client must not inherit global credential defaults. The
next address must satisfy public policy again.

The effective follow mode and hop limit are validated against operator policy.
An excessive limit already captured by the outer Guzzle redirect code is
rejected, rather than appearing bounded by an ineffective inner option change.
Custom wrappers set correct limits when constructing the client/request.
:literal:`allow_redirects=false` and PSR-18 return 30x unchanged.

Retry logic remains with the caller; every genuinely new attempt is authorized
again. Policy errors are not a reason for automatic retries.

.. _adr-0008-rejected-alternatives:

Rejected alternatives
=====================

- **Block only private redirect targets:** does not prevent credentials
  reaching public attacker targets.
- **Remove known authentication headers:** misses custom headers and body
  data.
- **Block every redirect:** unnecessary for same-origin/public fetch.
- **Inner clamp without effect on outer redirect state:** a false security
  promise.

.. _adr-0008-consequences:

Consequences
============

Generic requests with legitimate cross-origin redirects may break. The safe
special-purpose API deliberately has a small input surface. Method changes and
hop limits must be tested specifically for each major.

.. _adr-0008-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T045, T046, T047, T048, T049, T050, T051, T052, T081`. Details and
expected results are in `Original verification specification`_. These tests
were specified, not executed as part of the original documentation delivery.

.. _adr-0008-reassessment-trigger:

Reasons to reconsider
=====================

An API for credential-bearing cross-origin redirects would introduce a new
authorization model and requires explicit target/credential permissions and
its own ADR.

.. _adr-0008-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0008-redirects-und-credential-grenzen.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
