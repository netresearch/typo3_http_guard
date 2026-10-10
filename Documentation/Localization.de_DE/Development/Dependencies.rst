.. _dependency-report:

================================
Abhängigkeiten und Sicherheitsstand
================================

.. _dependency-report-current:

Gepatchte Core-Graphen
=====================

Core 13.4.36 und 14.3.8 verlangen SVG-Sanitizer 1.0.0. Diese Version behebt
die drei aufgezeichneten SVG-Befunde. Die sicheren Core-Untergrenzen stehen
in :literal:`^13.4.36 || ^14.3.8`; kompatible Updates bleiben zulässig.
Die Extension ersetzt oder verändert die Sanitizer-Abhängigkeit nicht.

Die vier dokumentierten vollständigen Core-/SDK-Graphen wählen Sanitizer
1.0.0 und melden keine bekannten Sicherheitsbefunde. Der Advisory-Block
bleibt aktiv; die aktuelle Fixture-Vorbereitung ignoriert keine Advisory-ID.
Die Produktions-Security-Jobs lösen getrennt den tatsächlichen Root-Graphen
für Core 13 und 14 auf. Sie prüfen die installierten APIs und erzeugen
je Graph Lock, Audit-JSON und CycloneDX-SBOM. Eine bekannte Sicherheitsmeldung
oder ein unerreichbarer Auditdienst lässt die Prüfung fehlschlagen.

Core 13 benötigt weiterhin das aufgegebene Upstream-Paket
:literal:`doctrine/annotations`. Die explizite Option
:literal:`--abandoned=report` bewahrt diesen Wartungshinweis und unterdrückt
keine Sicherheitsmeldung. Beim damaligen Stand liefert der Standardaudit
nur wegen dieses Hinweises Exitcode 1; Core 14 besteht den Standardaudit mit
Exitcode 0. Wartungsstatus und bekannte Sicherheitslücken sind getrennte
Aussagen.

Der dokumentierte Main-Security-Lauf
`37964357280 <https://github.com/netresearch/typo3_http_guard/actions/runs/37964357280>`_
besteht beide Produktionsgraphen am Commit :literal:`e2e125f`. Spätere
Änderungen brauchen neue einschlägige Ergebnisse vor dem Merge.

.. _dependency-report-composer:

Historische Graphen
===================

Der unveränderte
`Abhängigkeitsbericht am Commit 7a3a39b
<https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/docs/Abhaengigkeiten.md>`_
beschreibt Core 13.4.35 und 14.3.7 mit SVG-Sanitizer 0.22.0 und drei
Sicherheitsbefunden. Die anfangs 36 GitHub-Meldungen wiederholen dieselben
drei IDs in zwölf historischen Fixture-Locks. Manifeste und Locks bleiben
mit unveränderten Bytes, Originalpfaden und SHA256-Zuordnung in drei
gekennzeichneten ZIP-Archiven in der `Git-Historie
<https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/evidence/packaging/composer-locks/README.md>`_
und im externen Quellarchiv erhalten. Sie sind keine Installationsquellen
und werden nicht als aktueller Produktionsaudit dargestellt.

Die damaligen schmalen Testausnahmen erlaubten isolierte Core-Tests ohne
SVG-Verarbeitung. Sie belegen keine sichere SVG-Verarbeitung und gelten
nicht für eine aktuelle Produktionsinstallation.

.. _dependency-report-platform:

PHP, libcurl und Betriebssystem
==============================

Die libcurl-Untergrenze 7.59.0 stammt aus der benötigten Mehradressfunktion
von :literal:`CURLOPT_RESOLVE`. Sie ist keine Sicherheitsfreigabe des
Betriebssystems. Backports lassen sich nicht allein aus einer Versionsnummer
ablesen. PHP, libcurl, TLS-Bibliotheken und Betriebssystem müssen nach den
jeweiligen Herstellerhinweisen aktuell gehalten werden. Frühere Ubuntu-
und Alpine-Nachweise gelten nur für ihre tatsächlich aufgezeichneten
Paketrevisionen und Image-Digests.

.. _dependency-report-updates:

Updates
=======

Renovate verwaltet Composer- und Actions-Updates. Core-/SDK- und
Sicherheitsupdates benötigen Dashboard-Freigabe, Quellenvergleich und die
betroffenen Prüfungen; ihre automatische Übernahme ist abgeschaltet. Vier
frei aufgelöste native Core-13/14- und Guzzle-7/8-Zellen prüfen kompatible
Graphen bei Pull Requests und wöchentlich. Feste Zellen bewahren ihre
reproduzierbaren Quellstände.

Neue Hauptversionen, Untergrenzen, Handler-APIs, Optionen oder Policyrechte
benötigen die einschlägigen Policy-, Wire-, Core- und Mutationstests. Eine
erfolgreiche Composer-Auflösung allein belegt kein sicheres natives Senden.
Adressregeln werden während Entwicklung und Release aus Primärquellen
versioniert übernommen; der Requestpfad lädt keine Sicherheitslisten aus
dem Internet nach.
