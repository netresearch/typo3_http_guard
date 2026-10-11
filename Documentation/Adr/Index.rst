.. _decisions:
.. _adr-index:

=============================
Architecture decision records
=============================

ADRs retain the context, alternatives and consequences of architectural
choices. This is HTTP Guard's own numbering, independent of nr-vault's ADRs.

ADR-0001 through ADR-0014 preserve the complete proposals dated 8 October
2026. Their historical status does not impose a new approval gate on the
current alpha project. Current behavior and qualification are documented
in :ref:`security`, :ref:`development-requirements` and
:ref:`verification-report`.

ADR-0015 records the accepted single-extension decision dated 9 October
2026. It supersedes ADR-0002's package and publication proposal. ADR-0016
through ADR-0018 record the implemented public RequestFactory boundary,
controlled DNS wire resolution and single-use cURL factory on 10 October
2026. ADR-0017 supersedes ADR-0005's default-resolution proposal; its checked
address-set and connection-pinning principles remain applicable. The other
proposals are not superseded merely by the packaging change. Historical
alternatives remain part of the record and are not current API guidance.

.. _adr-status:

Record status
=============

* :ref:`adr-0001`: historical proposal.
* :ref:`adr-0002`: package proposal superseded by ADR-0015.
* :ref:`adr-0003`: historical proposal.
* :ref:`adr-0004`: historical proposal.
* :ref:`adr-0005`: default-resolution proposal superseded by ADR-0017;
  connection-pinning principles retained.
* :ref:`adr-0006`: historical proposal.
* :ref:`adr-0007`: historical proposal.
* :ref:`adr-0008`: historical proposal.
* :ref:`adr-0009`: historical proposal.
* :ref:`adr-0010`: historical proposal.
* :ref:`adr-0011`: historical proposal.
* :ref:`adr-0012`: historical proposal.
* :ref:`adr-0013`: historical proposal.
* :ref:`adr-0014`: historical proposal.
* :ref:`adr-0015`: accepted.
* :ref:`adr-0016`: accepted.
* :ref:`adr-0017`: accepted.
* :ref:`adr-0018`: accepted.

.. toctree::
    :maxdepth: 1

    ADR-0001-ScopeAndTrustBoundaries
    ADR-0002-LibraryAndIntegrations
    ADR-0003-MiddlewareAndControlledTransport
    ADR-0004-FailClosedAndModes
    ADR-0005-DnsAndConnectionPlan
    ADR-0006-ClientBoundEndpointGrants
    ADR-0007-SupportedTransports
    ADR-0008-RedirectsAndCredentials
    ADR-0009-NormalizationAndAddressClasses
    ADR-0010-CacheIsolationAndConcurrency
    ADR-0011-VaultStreamingAndCancellation
    ADR-0012-ErrorsAndObservability
    ADR-0013-CompatibilityAndMigration
    ADR-0014-SecurityEvidenceAndReleaseGates
    ADR-0015-SingleExtension
    ADR-0016-PublicRequestFactoryBoundary
    ADR-0017-ControlledDnsWireResolution
    ADR-0018-SingleUseCurlFactory

.. _adr-original-sources:

Original sources
================

Each historical ADR links to its immutable original record and specification
documents. The `original ADR index
<https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/README.md>`_
and `source catalogue S01-S23
<https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_
retain the proposal's provenance. Restoration does not turn specified
tests into executed tests or historical proposals into accepted decisions.
