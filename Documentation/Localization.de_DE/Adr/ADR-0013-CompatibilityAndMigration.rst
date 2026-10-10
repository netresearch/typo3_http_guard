.. _adr-0013:

=============================================================================
ADR-0013: Explizite Migration statt stiller Umdeutung bestehender Allowlisten
=============================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-005, HG-036, HG-037, HG-038, HG-043, HG-045
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0013 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0013-kompatibilitaet-und-migration.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0013-context:

Kontext
=======

Vault benutzt einen separaten Stack, erlaubt exakte flache Hosteinträge als
Ausnahme und führt ext-curl nur als Vorschlag. TYPO3s geprüfter Corestand
verwendet verschachtelte Kontextallowlists und erlaubt Guzzle 7 und 8. Eine
naive Extraktion kann damit zugleich Schutzgrenzen und Kompatibilität verändern.
[S01, S04-S06, S13, S16]

.. _adr-0013-decision:

Entscheidung
============

Zuerst werden gemeinsamer Korpus und Bibliothek implementiert, dann
Coreintegration und expliziter Vaultadapter. Keine Installation der globalen
Extension gilt automatisch als Vaultmigration. OAuth-Tokenleg und Resourceleg
erhalten getrennte Autorisierung und Pins.

Ein Legacyreport erfasst vorhandene Konfiguration, schreibt aber keine aktive
interne Freigabe. Betreiber müssen Scheme, Port, CIDRs, Methoden, Zweck,
Verantwortung und Ablauf bewusst ergänzen. Leere DNSantworten werden nicht mehr
durch Namensfreigabe legitimiert; statische Mappings ersetzen notwendige
Hosts-/NSS-Sonderfälle.

Versionswechsel und Releasehinweise benennen neue cURLpflicht,
Proxy-/Streamgrenzen und strengere Ausnahmen. Entfernen bestehenden
Legacyverhaltens erfolgt ausdrücklich versioniert. Die Bibliothek hat keinen
versteckten Legacy-Fail-open-Schalter. Die unterstützten
PHP-/TYPO3-/Guzzle-/PSR-Kombinationen werden durch aufgelöste Locks und
tatsächliche CI belegt.

.. _adr-0013-rejected-alternatives:

Verworfene Alternativen
=======================

**Alte Listen automatisch konvertieren:** wichtige Berechtigungsdimensionen
fehlen. **Globale Middleware als Vaultschutz deklarieren:** technisch falsch.
**Securityfix still mit Architekturmigration verbinden:** erschwert Rollback und
Review. **Alle Major-Kombinationen versprechen, die Composer erlaubt:** ersetzt
keinen Transporttest.

.. _adr-0013-consequences:

Konsequenzen
============

Migration ist nachvollziehbar und rollbackfähig, aber nicht konfigurationsfrei.
Legacy und neuer Pfad können zeitlich getrennt verfügbar sein; Diagnose muss
klar zeigen, welcher aktiv ist. Secrets-/Auditregressionen sind eigene
Freigabekriterien.

.. _adr-0013-verification-and-acceptance:

Nachweis und Abnahme
====================

T039, T041, T042, T069, T070, T071, T072, T073, T080, T082. Details und
erwartete Ergebnisse stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert,
nicht im Rahmen dieser Dokumentlieferung ausgeführt.

.. _adr-0013-reassessment-trigger:

Anlass für Neubewertung
=======================

Neue Core-/Guzzle-Majors oder eine Änderung des globalen Erweiterungspunkts
erfordern erneute Integrationsabnahme. Ein gepflegter Legacyzweig wird nicht als
gleichwertiger Schutz beworben.

.. _adr-0013-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
