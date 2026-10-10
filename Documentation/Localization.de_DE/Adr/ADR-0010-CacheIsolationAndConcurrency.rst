.. _adr-0010:

======================================================================================
ADR-0010: Adressmemoisierung erlauben, Berechtigungs- und Verbindungspooling isolieren
======================================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-015, HG-025, HG-026, HG-027, HG-040, HG-044
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0010 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0010-cache-isolation-und-parallelitaet.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0010-context:

Kontext
=======

Mehrere gleichzeitige Requests können dieselbe Origin unter unterschiedlichen
Freigaben oder DNSantworten erreichen. Guzzles/cURLs Pooling und optionale
Share-Handles können DNS- oder Verbindungszustand wiederverwenden. Ein neuer Pin
allein ist deshalb kein ausreichender Nachweis requestbezogener Isolation. [S12,
S14]

.. _adr-0010-decision:

Entscheidung
============

Version 1 isoliert jede unabhängige Transferlease samt cURL-Multi-Handle, DNS-
und Connectionzustand. Keine gemeinsamen Share-Handles, keine Übernahme fremder
Poolverbindungen, keine stillen Alt-Svc- oder HSTS-Routenwechsel. Ein Request
mit internem Grant darf keinen späteren Public-Request über seinen
Verbindungspool privilegieren.

Ein begrenztes positives DNSmemo ist erlaubt: standardmäßig höchstens fünf
Sekunden und 32 Hosts, bei kürzerem bekannten TTL entsprechend weniger. TTL 0
und negative Antworten werden nicht positiv gecacht. Schlüssel enthalten
Resolveridentität/-konfiguration. Jede Verwendung klassifiziert die Adressen neu
gegen die aktuelle Policy; es wird niemals ein boolesches Allow gecacht.

ConnectionPlans sind einmalige, kurzlebige Versuchsdaten. Verzögerte Arbeit
autorisiert beim tatsächlichen neuen Versuch, nicht bei ihrer Einplanung.
Policywechsel gelten für neue Versuche; bereits laufende erlaubte Transfers
werden nicht als automatisch widerrufen ausgegeben.

.. _adr-0010-rejected-alternatives:

Verworfene Alternativen
=======================

**Gemeinsamer Pool plus Pin:** erfordert zusätzliche, komplexe
Isolationsevidenz. **Globaler Host-Allow-Cache:** vermischt Kontexte. **Gar
keine Memoisierung:** sicher möglich, verursacht aber vermeidbare
Doppelauflösung. **Live-Widerruf laufender Transfers:** eigenes Laufzeitfeature,
nicht durch einen Cacheflush erledigt.

.. _adr-0010-consequences:

Konsequenzen
============

Parallele Sicherheitsentscheidungen bleiben voneinander unabhängig. v1
verzichtet auf Wiederverwendung über unabhängige Versuche und akzeptiert
Handshakekosten. Bei hohem Requestvolumen muss dies gemessen werden; die
Bibliothek puffert Bodies nicht zusätzlich.

.. _adr-0010-verification-and-acceptance:

Nachweis und Abnahme
====================

T038, T052, T053, T054, T055, T056, T057, T075, T079, T084. Details und
erwartete Ergebnisse stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert,
nicht im Rahmen dieser Dokumentlieferung ausgeführt.

.. _adr-0010-reassessment-trigger:

Anlass für Neubewertung
=======================

Pooling kann erst nach einer neuen ADR mit Schlüssel-/Invalidierungsmodell,
Concurrencytests und Wire-Beweis eingeführt werden. Ein Performanceziel allein
ist kein Nachweis.

.. _adr-0010-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
