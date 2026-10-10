.. _decisions:
.. _adr-index:

===============================
Architekturentscheidungen (ADRs)
===============================

ADRs halten Anlass, Alternativen und Folgen einer Architekturentscheidung
fest. Die Nummerierung gehört zu HTTP Guard und ist unabhängig von den
ADR-Nummern in nr-vault.

ADR-0001 bis ADR-0014 bewahren die vollständigen Vorschläge vom 8. Oktober
2026. Ihr historischer Status erzeugt keine neue Freigabeanforderung für das
aktuelle Alpha-Projekt. Der heutige Funktions- und Nachweisstand steht unter
:ref:`security`, :ref:`development-requirements` und :ref:`verification-report`.

ADR-0015 hält die angenommene Ein-Extension-Entscheidung vom 9. Oktober 2026
fest. Sie löst die Paket- und Veröffentlichungsvorgabe aus ADR-0002 ab.
ADR-0016 bis ADR-0018 dokumentieren am 10. Oktober 2026 die umgesetzte
öffentliche RequestFactory-Grenze, kontrollierte DNS-Wire-Auflösung und
einmalige cURL-Factory. ADR-0017 löst den Vorschlag zur Standardauflösung aus
ADR-0005 ab; dessen Prüfung der Adressmenge und Verbindungspinning bleiben
gültig. Die anderen Vorschläge werden durch den Paketwechsel nicht pauschal
abgelöst. Historische
Alternativen bleiben Teil der Dokumentation und sind keine heutige
API-Anleitung.

.. _adr-status:

Status der Entscheidungen
=========================

* :ref:`adr-0001`: historischer Vorschlag.
* :ref:`adr-0002`: Paketvorschlag durch ADR-0015 abgelöst.
* :ref:`adr-0003`: historischer Vorschlag.
* :ref:`adr-0004`: historischer Vorschlag.
* :ref:`adr-0005`: Standardauflösung durch ADR-0017 abgelöst;
  Verbindungspinning bleibt gültig.
* :ref:`adr-0006`: historischer Vorschlag.
* :ref:`adr-0007`: historischer Vorschlag.
* :ref:`adr-0008`: historischer Vorschlag.
* :ref:`adr-0009`: historischer Vorschlag.
* :ref:`adr-0010`: historischer Vorschlag.
* :ref:`adr-0011`: historischer Vorschlag.
* :ref:`adr-0012`: historischer Vorschlag.
* :ref:`adr-0013`: historischer Vorschlag.
* :ref:`adr-0014`: historischer Vorschlag.
* :ref:`adr-0015`: angenommen.
* :ref:`adr-0016`: angenommen.
* :ref:`adr-0017`: angenommen.
* :ref:`adr-0018`: angenommen.

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

Ursprüngliche Quellen
====================

Jeder historische ADR verweist auf seinen unveränderlichen Originalstand
und die ursprünglichen Spezifikationen. Der `ursprüngliche ADR-Index
<https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/README.md>`_
und das `Quellenverzeichnis S01-S23
<https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_
bewahren die Herkunft der Vorschläge. Wiederhergestellte ADRs machen aus
damals spezifizierten Tests keine ausgeführten Prüfungen und aus
historischen Vorschlägen keine angenommenen heutigen Entscheidungen.
