.. _changelog:

=========
Changelog
=========

.. _changelog-013:

Version 0.1.3 (2026-10-11)
==========================

Dieser Alpha-Patch gleicht den kanonischen Build und die Git-Quellarchive an den
Paketumfang des TER an. Die beiden Editor-Konfigurationsdateien der Dokumentation
bleiben im Repository und werden aus den installierbaren Archiven ausgeschlossen.

Die Prüfung unveränderlicher Quellstände liest ausschließlich ihre beiden
expliziten Entwicklungsdatei-Deklarationen als Daten. Historische Releases
behalten ihren dokumentierten Paketinhalt.

.. _changelog-012:

Version 0.1.2 (2026-10-11)
==========================

Diese Alpha-Wartungsversion präzisiert die Behandlung ungültiger Eingaben
und den Transfer-Lifecycle. IPv4-/DNS-Authorities in eckigen Klammern und
ungültige Resolverdatensätze werden vor dem Verbindungsplan abgelehnt.
Konfiguration und Transportoptionen werden validiert; Leases ohne Request
oder unterstützte Laufzeit werden beendet und freigegeben. Meldungen bleiben
begrenzt und enthalten keine vertraulichen Rohdaten.

Die Entwicklung verwendet den gemeinsamen Coding-Style, statische Analyse
auf Level 10, Architektur-, Fuzz-, Policy-Performance- und Mutationstests
für vollständige Dateien. Coverageberichte beziehen sich auf gemessene
Repositorypfade; die Releaseprüfung nutzt unterstützte Provenanceoptionen.
Symfony Console 8 ist neben 7.4 in kompatiblen Dependencygraphen zulässig.

Alle vierzehn ursprünglichen nummerierten Architekturvorschläge stehen wieder
vollständig auf Englisch und Deutsch unter :ref:`adr-index`. ADR-0015 bis
ADR-0018 dokumentieren die angenommenen Entscheidungen zu Paketaufbau,
öffentlicher RequestFactory, kontrollierten DNS-Abfragen und Einmal-cURL-Factory
sowie deren Bezug zu den historischen Vorschlägen. Die Wiederherstellung
ändert kein Laufzeitverhalten und macht geplante Tests nicht zu ausgeführten.

Aktuelle Anforderungen und Entscheidungen stehen im Handbuch, Tests unter
Tests und unterstützende Werkzeuge unter Build. Originalentwürfe, alte Läufe
und die externe Vault-Referenzintegration bleiben in unveränderter Git-Historie.
Die Extension enthält weiterhin ihren Sicherheitskern und das englische und
deutsche Handbuch in einem Paket. Der registrierte RequestFactory-Pfad erhält
Standardschutz ohne eine Vault-Migration.

Version 0.1.1 (2026-10-09)
==========================

Die Ereignisauswahl des gemeinsamen DCO-Workflows wurde für Pull Requests und
Main-Pushes korrigiert. Die tatsächlich veröffentlichten Artefakte und ihre
Prüfung sind unter :ref:`release-provenance` dokumentiert.

Version 0.1.0 (2026-10-09)
==========================

Erster Alpha-Quellstand mit eingebettetem Sicherheitskern, TYPO3-Integration,
Endpoint-Clients, kontrolliertem nativem Transport und Offline-Diagnose.
Der Publisher des ersten Tags wurde vor Artefakten und TER-Veröffentlichung
blockiert. Version 0.1.1 behielt den Schutz bei und korrigierte diesen Workflow.

Der `vollständige Changelog des Repositorys
<https://github.com/netresearch/typo3_http_guard/blob/main/CHANGELOG.md>`_
erhält die Originaleinträge. Prüfergebnisse gelten für ihren dokumentierten
Quellstand und nicht automatisch für spätere Versionen. Der Alpha-Status
belegt keine menschliche Sicherheitsprüfung, keinen Betreiberpilot, keine
Zertifizierung und keine externe Freigabe des Online-Handbuchs.
