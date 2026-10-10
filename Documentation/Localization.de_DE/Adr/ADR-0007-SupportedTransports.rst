.. _adr-0007:

======================================================================
ADR-0007: Nur kontrolliertes cURL; Proxies und PHP-Streams nicht in v1
======================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-005, HG-020, HG-021, HG-022, HG-028, HG-042
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0007 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0007-unterstuetzte-transporte.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0007-context:

Kontext
=======

Ein cURL-Pin wird von PHP-Streams nicht umgesetzt. Bei Proxybetrieb kann die
Originauflösung beim Proxy liegen; die lokal geprüfte IP ist dann kein Beweis
für dessen Zielverbindung. Guzzle-Majors unterscheiden sich in erlaubten
Raw-Optionen. [S05, S08, S12, S13, S17]

.. _adr-0007-decision:

Entscheidung
============

Enforce verlangt einen getesteten cURL-/curl-multi-Adapter. :literal:`stream=true`,
fremde Handler, rohe cURL-Optionen und nicht zertifizierte
Transportsharing-Einstellungen werden abgelehnt. cURL-Konstanten und Verhalten
werden auf tatsächliche Verfügbarkeit geprüft. Die funktionale
Multi-Address-Mindestversion allein ist keine akzeptierte Securitybaseline.

Version 1 unterstützt keine Proxies. Explizite und tatsächlich geerbte
Proxykonfiguration wird erkannt; weder Versand darüber noch stilles Umgehen ist
erlaubt. Nach bestandener Prüfung unterbindet der kontrollierte Adapter
unbeabsichtigtes Proxyerben. Ein eingehender HTTP-Header :literal:`Proxy` ist
keine vertrauenswürdige Prozesskonfiguration.

Die gemeinsame Bibliothek enthält getrennte, getestete
Guzzle-7/8-Optionenadapter. Transportumlenkende Optionen bleiben Guard-eigen.
CA-Bundles, mTLS und freigegebene Timeouts werden nicht durch rohe
Overrideoptionen ersetzt.

.. _adr-0007-rejected-alternatives:

Verworfene Alternativen
=======================

**Alle Guzzlehandler zulassen:** keine einheitliche Garantie. **Proxy als
privaten Endpoint allowlisten:** prüft nur den Proxy, nicht dessen
Originzugriff. **Automatischer Direct-Fallback:** umgeht Unternehmensrouting.
**Jedes beliebige Raw-cURL-Flag durchreichen:** öffnet unabhängige
Ziel-/Resolverwege.

.. _adr-0007-consequences:

Konsequenzen
============

Der v1-Umfang ist klar und prüfbar, schließt aber Proxyinstallationen aus. Ein
Supportfehler ist sichtbar statt still unsicher. Die Entwicklung muss echte
Versionskombinationen testen, nicht nur Composerauflösung.

.. _adr-0007-verification-and-acceptance:

Nachweis und Abnahme
====================

T040, T041, T042, T043, T044, T058, T080, T084. Details und erwartete Ergebnisse
stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert, nicht im Rahmen
dieser Dokumentlieferung ausgeführt.

.. _adr-0007-reassessment-trigger:

Anlass für Neubewertung
=======================

Ein Proxyadapter braucht eine eigene ADR, ein definiertes Trust-Modell und einen
Nachweis am tatsächlichen Originpfad. PHP-Streams benötigen ebenfalls eine
eigenständig nachgewiesene Bindung vor Freigabe.

.. _adr-0007-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
