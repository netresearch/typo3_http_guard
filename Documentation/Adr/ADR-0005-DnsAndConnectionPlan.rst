.. _adr-0005:

=================================================================
ADR-0005: Bind the checked address set directly to the connection
=================================================================

:Status: Default-resolution proposal superseded by :ref:`adr-0017`
    (original status: Proposed); connection-pinning principles retained.
:Date: 2026-10-08
:Original decision makers: Project architecture/security owners; approval
    still pending in the original proposal.
:Requirements: HG-013, HG-014, HG-015, HG-016, HG-027, HG-044
:Original supersession: None; a new decision for the proposed product.

.. note::

    This is the full English translation of the historical proposal dated
    2026-10-08, originally Proposed. Its pending approval is historical and
    creates no additional alpha approval gate. Current
    :ref:`security`, :ref:`development`, :ref:`verification-report` and
    :ref:`development-requirements` describe the operative scope and evidence.

    The later single-package decision is recorded in :ref:`adr-0015`.

    :ref:`adr-0017` replaces the historical default-resolution proposal with
    the implemented DNS wire backend. The original address-set checks and
    connection-pinning principles are retained.

.. _adr-0005-context:

Context
=======

A precheck and independent transport resolution can return different IPs.
Vault addressed this with :literal:`CURLOPT_RESOLVE` and subsequently treated
empty answers as failure. It nevertheless retains a literal allowlist
exception. DNS and NSS/hosts resolution are not equivalent.

Sources: :literal:`[S05-S07, S09, S19]`.

.. _adr-0005-decision:

Decision
========

After normalization and policy checks, every attempt creates an immutable
ConnectionPlan. All usable A/AAAA addresses are checked; one prohibited
candidate rejects the entire attempt. No usable answer means denial, even for
an approved host. Static host mappings also supply addresses that must be
checked, not blanket permission.

The transport receives exactly that set in a multi-address pin for each
host/port pair. The original hostname, TLS SNI and certificate validation are
preserved. An unreachable pin must not trigger unchecked DNS fallback. Invalid
records, pin formats or failed option setters must not be silently ignored.

Resolution scope and memoization are bounded. Synchronous
:literal:`dns_get_record()` is not presented as a resolver that can be
forcibly interrupted; operating-system limits and measured behavior form part
of qualification.

.. _adr-0005-rejected-alternatives:

Rejected alternatives
=====================

- **Fresh DNS before every send alone:** still leaves a check-to-connect
  window.
- **Filter out only dangerous IPs:** hides mixed trust zones and is rejected
  for v1.
- **Let an explicit host continue uncontrolled on DNS failure:** violates the
  connection-bound guarantee.
- **Replace the URL with an IP:** complicates correct host/TLS semantics.

.. _adr-0005-consequences:

Consequences
============

Selection is traceable and testable. Split-DNS configurations with mixed
allowed/prohibited answers are deliberately denied. Names unknown to DNS need
a static mapping or a resolver adapter certified later.

.. _adr-0005-verification-and-acceptance:

Evidence and acceptance in the original proposal
================================================

:literal:`T022, T023, T024, T025, T026, T027, T028, T029, T030, T031, T032, T033, T078`.
Details and expected results are in `Original verification specification`_.
These tests were specified, not executed as part of the original documentation
delivery.

.. _adr-0005-reassessment-trigger:

Reasons to reconsider
=====================

Additional resolvers may be included if they deliver candidates completely and
within bounds, and never release transport resolution without control.

.. _adr-0005-related-documents:

Related documents
=================

`Original ADR`_, `Original product requirements`_, `Original security model`_,
`Original architecture`_ and `Original source catalogue`_ (:literal:`S01-S23`),
plus the :ref:`ADR index <adr-index>`. :ref:`adr-original-sources` explains
the immutable source index.

.. _Original ADR: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0005-dns-und-connection-plan.md
.. _Original product requirements: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md
.. _Original security model: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md
.. _Original architecture: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md
.. _Original verification specification: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md
.. _Original source catalogue: https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md
