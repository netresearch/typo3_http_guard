.. _development-assessment:

=====================
Projektprüfung
=====================

.. _assessment-scope:

Quellstände und Messarten
========================

Die ursprüngliche Prüfung mit den Netresearch-Skills Automated Assessment,
TYPO3 Conformance und Enterprise Readiness gehört zum Commit
:literal:`7a3a39bbaa763eb876ff4c9cdc743b689c557590`. Mechanische Ergebnisse,
Agentenreviews, Anwendbarkeitsentscheidungen und Laufzeitmessungen bleiben
getrennte Nachweise. Übersprungene Prüfungen sind keine bestandenen
Prüfungen.

Die elf relevanten Kataloge ergeben ursprünglich 755 mechanische
Prüfungen: 548 bestanden, 164 fehlgeschlagen und 43 übersprungen. Die
Security-Prüfung verifiziert zuvor alle 1.912 gebundenen Quellhashes und
liefert bei 376 Einträgen 364 bestandene, sieben fehlgeschlagene und fünf
übersprungene Ergebnisse.

Die 180 tatsächlichen Modellprüfungen liefern 65 bestandene Ergebnisse,
28 Befunde, 81 Anwendbarkeits- oder Umfangsausnahmen, drei zurückgestellte
Releasepunkte und drei Anforderungen an weitere Nachweise. Vier ausführbare
Prüfungen sind im Katalog falsch im Modellabschnitt abgelegt; ihre separat
ausgeführten Bedingungen bestehen. Der Definitionsfehler bleibt sichtbar.
Agentenreviews werden nicht als menschliche Sicherheitsprüfung bezeichnet.

Ein späterer vollständiger Conformance-/Enterprise-Lauf umfasst 135 Einträge
mit 109 bestandenen, 25 fehlgeschlagenen und einem übersprungenen Ergebnis.
Die gezielte Korrektur des TER-Titelpräfixes ergibt im dokumentierten
Abgleich 110 bestandene, 24 fehlgeschlagene und einen übersprungenen Eintrag.
Die Rohdaten werden nicht durch diese Zusammenführung ersetzt.

.. _assessment-runtime-evidence:

Aufgezeichnete Laufzeitmessungen
===============================

Die eingefrorene Baseline besteht 145 Tests mit 2.253 Assertions. Unter PHP
8.5 mit Xdebug beträgt die Zeilenabdeckung 80,80 %: 1.873 von 2.318
ausführbaren Zeilen. Die Methodenabdeckung beträgt 48,50 %: 97 von 200
Methoden. Die Unit-Suite allein besteht 96 Tests mit 1.173 Assertions und
57,25 % Zeilenabdeckung. Die echten Core-Bootstrap-, CLI- und klassischen
Prozesse tragen nicht zu diesen Coveragewerten bei.

Spätere semantische und native Prüfläufe behalten ihre eigenen Mengen,
SDK-Tupel und Quellhashes. Sie stehen unter :ref:`verification-report`.
Die Baseline-Coverage wird ihnen nicht zugerechnet und ist nicht der
öffentliche Codecov-Wert.

Der korrigierte öffentliche Codecov-Bericht gehört zu Main
:literal:`27a958a`: 82,06 % Zeilenabdeckung bei 2.609 gemessenen Zeilen in
63 Dateien, davon 2.141 Treffer und 468 nicht ausgeführte Zeilen. Er belegt
keine Zweigabdeckung. Die integrierte Offline-Unit-Suite besteht je
**1.535 Tests mit 7.199 Assertions** auf echten Core-13-/Guzzle-7- und
Core-14-/Guzzle-8-Graphen unter PHP 8.5.11. Der frühere lokale Stand besteht
340 Unit-Tests mit 2.125 Assertions. Spätere Quelländerungen, Qualitätsläufe
und Mutationsumfänge behalten eigene Bindungen unter
:ref:`assessment-reconciliation`.

Abgeschlossene lokale Level-10-Prüfungen für Kern und echten Core 13/14
sowie die Architekturprüfung melden keine Fehler; Rector schlägt keine
Änderungen vor. Die aufgezeichnete Stilprüfung findet keine Änderungen in
**196 Dateien**: 143 im Extension-/Test-/Tool-Bereich und 53 im eingebetteten
MIT-Kern. Alle 173 aktuellen Unit-Eingaben bleiben bytegleich. Frühere
Scannerkommentare behalten ihren separat bestätigten identischen
ausführbaren AST und ihre historische Bytebindung.

.. _assessment-repository-corrections:

Projektkonventionen
===================

Das englische Handbuch liegt unter :file:`Documentation/`, die deutsche
Fassung unter :file:`Documentation/Localization.de_DE/`. README,
Sicherheitsrichtlinie und Beitragsanleitung sind englisch. Der frühere
:file:`docs/`-Inhalt wurde in das Handbuch übernommen. Historische Quellen,
Spezifikationen und ADRs bleiben unverändert erhalten.

Die Produktionsbereiche erlauben PHP :literal:`^8.2` und Core
:literal:`^13.4.36 || ^14.3.8`. Kompatible Patches und Minor-Versionen
benötigen keine neue Extension-Version. Die tatsächlichen Core-/SDK-APIs
und Transportfähigkeiten werden geprüft. Eine erlaubte künftige Version
ist damit noch nicht als tatsächlich ausgeführt dokumentiert.

Die aufgezeichnete Main-Konfiguration verlangt signierte Commits und die
CI-, Verification- und Security-Gates auch für Administratoren. Die
regelbasierte PR-Freigabe ist eine automatisierte Repositoryprüfung; sie
belegt keine unabhängige menschliche Sicherheitsprüfung. Die genauen
Ausführungen und aktuellen Maßnahmen stehen unter
:ref:`assessment-reconciliation`.

.. _assessment-open-work:

Aktueller Abgleich
==================

Der eingefrorene 940-ID-Abgleich, die nachfolgenden Maßnahmen und die
externen Grenzen stehen auf einer eigenen Seite. Dadurch bleiben
ursprüngliche Fehlerzahlen sichtbar, ohne bereits behobene Befunde erneut
als aktuelle Projektfehler zu behandeln. Ein bestandener Kataloglauf ist
keine Enterprise-Zertifizierung oder SLSA-Level-3-Freigabe.

Der Nutzer hat eine unabhängige menschliche Sicherheitsprüfung und einen
repräsentativen Betreiberpilot aus der Alpha-Abnahme herausgenommen.
Beide bleiben unvollendet und Empfehlungen für die Produktivbewertung. Sie werden
weder als bestanden noch als neue Alpha-Blocker behandelt.

Die erste abgeschlossene native Gesamtmessung liefert bei 3.790 Mutanten
66,07 % MSI und 73,47 % Covered MSI. Ein späterer Stand mit 556 Eingaben
misst 86,29 %/88,65 % bei 3.794 Mutanten; er liegt vor den jüngsten
Reporter-, DNS-, Lease- und Runner-Aufräumkorrekturen. Beide Läufe scheitern
an den unveränderten Zielen von 90 %/90 %. Die abschließende Messung mit
263 unveränderten Eingaben erreicht **90,43 % MSI/91,54 % Covered MSI**,
Exit 0, keine übersprungenen/ignorierten Mutanten und 21.601 unveränderte
Vendor-Dateien. Der frühere Lauf mit 90,46 %/91,57 % ohne vorab erfasste
Hashes beider Einstiegspunkte bleibt begrenzte Historie. Gezielte
Vertragstests behalten ihren eigenen Umfang. Die benannte GitHub-Policy-Referenz
misst 0,604912 ms p95 bei einer Grenze von 2 ms. Jede Messung behält ihre
eigene Quell- und Laufzeitbindung. Der veröffentlichte 940-ID-Abgleich bewahrt Rohbefunde
und begründet seine Einstufungen separat. Für OpenSSF sind die
Registrierungsangaben vorbereitet; der angemeldete Browserzugang ist
technisch nicht verfügbar. Daraus werden keine erreichten Badge-Stufen
oder zusätzlichen Freigabebedingungen abgeleitet.
