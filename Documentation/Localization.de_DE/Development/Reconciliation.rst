.. _assessment-reconciliation:

=====================
Abgleich der Prüfungen
=====================

.. _reconciliation-inventory:

Eingefrorenes 940-ID-Inventar
===========================

Der eingefrorene Abgleich am Commit :literal:`e2e125f` umfasst **940
verschiedene IDs** aus elf relevanten Katalogen. Für jede ID gibt es eine
Entscheidung; keine fehlt und keine wird doppelt gezählt. Kataloghashes und
historische Rohdaten bleiben unverändert. Die nachfolgenden
Dokumentationsänderungen beginnen bei :literal:`475ef55`, nach dem geprüften
Dependency-PR 10.

756 IDs sind mechanisch: 755 wurden ausgeführt, AH-37 blieb durch seine
Bedingung ausgeschlossen. Das ist kein bestandenes Ergebnis. Daneben gibt
es 180 tatsächliche Modellprüfungen und vier Definitionsfehler: SA-55 bis
SA-58 enthalten mechanische Bedingungen im Modellabschnitt. Die separat
ausgeführten Bedingungen bestehen, der Definitionsfehler bleibt erhalten.

Der ursprüngliche mechanische Lauf liefert 548 bestandene, 164
fehlgeschlagene und 43 übersprungene Ergebnisse. Der eingefrorene spätere
Lauf liefert **617 bestandene, 117 fehlgeschlagene und 21 übersprungene
Ergebnisse**, ohne blockierte Einträge. Die 180 ursprünglichen
Agentenreviews liefern 65 bestandene Ergebnisse, 28 Befunde, 81
Anwendbarkeits- oder Umfangsausnahmen, drei zurückgestellte Releasepunkte
und drei Anforderungen an weitere Nachweise. Dieser Inventarlauf erzeugt
keine neuen Modellantworten.

.. list-table:: Eingefrorene Entscheidungen je ID
    :header-rows: 1

    * - Einstufung
      - IDs
    * - Bereits erfüllt
      - 378
    * - Bereits behoben
      - 89
    * - Nicht anwendbar
      - 358
    * - Offen und anwendbar
      - 108
    * - Externe Bedingung
      - 3
    * - Fehler im Kriterium
      - 4

Die Summe ist 940. Diese Einstufungen sind keine Erfolgsquote oder
Compliance-Prozentzahl. Eine Auslassung, externe Bedingung oder ein
Kriterienfehler ist kein bestandenes Ergebnis. Bereits vorhandene passende
Nachweise werden nicht als neu implementierte Korrektur ausgegeben.
Das Inventar und spätere Entscheidungen liegen im
`Nachweisverzeichnis
<https://github.com/netresearch/typo3_http_guard/tree/main/Build/Reports/Assessment/completion>`_.
Neue Nachweise ergänzen Quelle und Ergebnis; sie überschreiben keine
alten Rohdaten oder Katalogdefinitionen.
Die unveränderte Inventardatei im Nachweisverzeichnis hat den SHA-256-Hash
:literal:`fd2b0df7c7b532269929804a39441b55dfeb5272a40231e8b06ef7d613e9f767`.
Die separate :file:`reconciliation.json` führt alle 940 IDs genau einmal
mit ursprünglichem Ergebnis, aktuellem Rohbefund und begründeter
Einstufung. Der frühere Arbeitsstand :literal:`fd7f6d8` enthält 684 Erfolge,
61 Fehler und zehn Auslassungen vor späteren Hook-, Functional- und
Lizenzkorrekturen. Der vollständige Stand :literal:`c823234` friert vor
Erzeugung der Abschlussberichte 850 Dateien ein: 755 anwendbare Prüfungen
liefern **690 Erfolge, 55 Fehler und zehn Auslassungen**; AH-37 bleibt
ausgeschlossen. Wörtliche Regex-Ergebnisse, Quellprüfung, begründete
Kalibrierung und Anwendbarkeit bleiben getrennt. Sie behaupten nicht,
dass alle 940 Punkte bestanden sind. Spätere Berichte und Dokumentation
gehören nicht zu den unveränderten Bytes dieses Laufs. Spätere
Korrekturen der Fixture-Prüfung und der Baseline erhalten eigene aktuelle Prüfbelege.

.. _reconciliation-calibration:

Anwendbarkeit und Prüfergrenzen
==============================

Ein starrer :file:`docs/`-Pfad erkennt das gültige TYPO3-Handbuch und seine
ADRs unter :file:`Documentation/` nicht. Dafür wird kein zweites Handbuch
angelegt. Zulässige Leerzeichen in PHP-Deklarationen sind kein Syntaxfehler;
heruntergeladene Forschungsquellen sind keine aktiven Fluid-Templates.
Tatsächlich verwendete PHPStan-Profile und Includes werden geprüft, statt
fehlende Konfiguration aus einem Dateinamenmuster abzuleiten.

Die echten Organisationstemplates liegen unter ihren kleingeschriebenen
Rootpfaden. Ein 404 an einem geratenen Großbuchstabenpfad belegt keine
vollständige Abwesenheit. Factory-Komposition und echte Core-Bootstraps
sind eigene Nachweise gegenüber generischer Service-Autoerkennung.
Datenbank-, TCA- und Frontendprüfungen ohne passende Projektoberfläche
behalten ihre Anwendbarkeitsentscheidung.

.. _reconciliation-documentation:

Dokumentationspunkte und externe Dienste
=======================================

.. list-table:: Aktuelle Dokumentationsmaßnahmen
    :header-rows: 1

    * - IDs
      - Maßnahme und Status
    * - GH-11, ER-22
      - Echter Main-CI-Badge mit Workflowlink ergänzt.
    * - GH-10, TT-43, ER-23
      - Main :literal:`27a958a` ist vollständig verarbeitet:
        82,06 % gemessene Zeilenabdeckung.
    * - ER-04
      - Scorecard-Badge verlinkt: tatsächlicher Wert 7,5 bei
        :literal:`475ef55` am 9. Oktober 2026.
    * - ER-06
      - Bestehende v0.1.1-Signaturen und Archiv-Provenance verifiziert;
        keine SLSA-Level-3-Behauptung.
    * - ER-05, ER-24
      - Registrierung vorbereitet, angemeldeter Browserzugang nicht verfügbar;
        keine erfundenen Badges oder erreichten Stufen.
    * - TD-21
      - Veröffentlichung und Alpha-Freigabe stimmen in README,
        Sicherheitsrichtlinie und beiden Handbuchsprachen überein.
    * - TD-26
      - Lange Prüfkapitel in navigierbare Seiten aufgeteilt.
    * - GH-15, GH-16
      - Badge-Reihenfolge und ausdrückliche Beitrags-/Entwicklungsabschnitte
        mit Credits ergänzt; externe Badge-Lücken bleiben sichtbar.

NB-20 ist separat an den tatsächlichen Repositorymetadaten geprüft: Die
Beschreibung endet mit :literal:`- by Netresearch`; die Änderung hat ihren
eigenen Metadatennachweis.

Die öffentliche Codecov-API meldet am 9. Oktober 2026 für Main
:literal:`27a958a8e7549a9154deb11a1c85bef29c568fa2` den Zustand
:literal:`complete`: **63 Dateien, 2.609 gemessene Zeilen, 2.141 Treffer und
468 nicht ausgeführte Zeilen, also 82,06 % Zeilenabdeckung**. Dieser Bericht
ersetzt den früheren Verarbeitungsfehler. :literal:`branches=0` belegt
weder eine gemessene Zweigabdeckung noch das Fehlen von Zweigen im Code.
Die lokale historische Baseline von 80,80 % bleibt eine andere Messung. Der
`echte Projektlink <https://app.codecov.io/gh/netresearch/typo3_http_guard>`_
bleibt überprüfbar.

Scorecard-Werte gehören zu ihrer Quelle und ihrem Datum. Ein erfolgreicher
Workflow ergibt keine bestimmte Punktzahl oder Best-Practices-/Baseline-Stufe.
Die unterstützten Suchen nach der genauen Repository-URL und dem Projektnamen
liefern jeweils eine leere Liste. Metadaten und belegte Kriterienvorschläge
sind vorbereitet; der angemeldete Registrierungszugang ist nicht verfügbar.
Eine Projekt-ID oder gespeicherte Live-Bewertung ist noch nicht bestätigt.
Diese externe Zugangslücke ist unabhängig von der Alpha-Autorisierung.

.. _reconciliation-current-validation:

Neue Maßnahmen und ihre Ausführung
==================================

Die Überarbeitung installiert echte Commit-/Secret-Hooks unter
:file:`Build/`, importiert die gemeinsamen Makefile-Targets und führt echte
Core-Einstiege über :file:`Tests/Functional/` aus. Validierte Konfiguration,
Level 10, Stil/Rector, Architektur und Mutation haben getrennte Prüfwege.
Acht Offline-Workflowkontrollen erfassen hinzugefügte, geänderte und
umbenannte direkte sowie verschachtelte Produktionsdateien. Ein
übersprungener PR-Diff ist kein Mutationsergebnis.

Gezielte Regressionen fanden drei weitere Randfehler: geklammerte
IPv4-/DNS-Autoritäten, NUL-Bytes in A-/AAAA-Resolverdatensätzen und einen
Lease ohne Request. Die freigegebenen minimalen Korrekturen weisen jeden
Fall an seiner Grenze mit dem dokumentierten Grund zurück. Normale
Adress- und Lifecycle-Positivfälle bleiben erhalten. Die frühere
CIDR-NUL-Lexemkorrektur ist ein eigener historischer Fix. Fehlerhafte
Offline-Datensätze belegen keinen nativen DNS-Exploit; ABI-Doubles keinen
nativen Wire-Transfer.

Die integrierte Offline-Suite besteht je **1.535 Unit-Tests mit 7.199
Assertions** auf echten Core-13.4.36-/Guzzle-7.15.5- und
Core-14.3.8-/Guzzle-8.2.0-Graphen unter PHP 8.5.11. Diese lokalen Unit-Läufe
sind getrennt von nativer Wire- und Gesamtmutationsqualifikation.
Abgeschlossene Level-10-Prüfungen für Kern/Core 13/14, Architektur und Rector
sind sauber. Die Stilprüfung findet keine Änderungen in 143 Extension-/Test-/
Tool- und 53 MIT-Kern-Dateien. Alle 173 aktuellen Unit-Eingaben bleiben
bytegleich; frühere Scannerkommentare behalten ihre AST-/Bytebindungen.
Der vollständige Qualitätslauf und 118 Tool-Kontrollen bestehen bei 850
unveränderten Arbeitsdateien und 21.601 unveränderten Vendor-Dateien vor
Erzeugung der Abschlussberichte. Im früheren 280-Dateien-Beleg fehlen
Mutation-Bootstrap, Agentenanweisungen und Forschungsquellen. Öffentliche
Ableitungen bewahren den Rohhash und den vollständigen ursprünglichen
JSON-Wert; ihre Bytes sind keine Originalaufzeichnungen.

Frühere Gesamtmessungen behalten ihren Umfang: 66,07 %/73,47 % bei 420
Eingaben, 86,29 %/88,65 % bei 556 und 89,38 %/91,48 % bei 295. Der Lauf
mit 261 Eingaben erreicht 90,46 %/91,57 %, enthält aber keine vor dem
Start erfassten Hashes beider PHP-Einstiegspunkte. Er erfüllt deshalb
nicht die abschließende Prüfung vollständiger Eingaben.

Die abschließende Messung mit **263 unveränderten Eingaben** liefert
**3.794 Mutanten, 90,43 % MSI und 91,54 % Covered MSI**, Exit 0 und keine
übersprungenen oder ignorierten Mutanten. Alle Quell- und 21.601
Vendor-Dateien stimmen mit ihren Hashes vor dem Start überein. 262 Eingaben
entsprechen Root; die erzeugte :file:`composer.lock` gehört nur zum
Prüfstand. Beide PHP-Einstiegspunkte der Extension sind enthalten.
3.424 Mutanten werden durch Tests erkannt (90,2478 %); 293 bleiben
unerkannt, 46 ohne Abdeckung. Drei Fehler, vier Syntaxfehler und 24
Timeouts werden separat ausgewiesen. Das Tool zählt Fehler-/Syntaxfälle
zum MSI; bei :literal:`--with-timeouts` zählen Timeouts nicht dazu.
Beide 90-%-Ziele sind erreicht; Rohbelege bewahren Laufzeit und Quellumfang.

Die erste native Initialsuite besteht **389 Tests mit 3.255 Assertions**
unter PHP 8.5.10/Xdebug 3.5.3/Core 14.3.8/Guzzle 8.2.0. Drei nacheinander
ausgeführte Wiederholungen verwenden Seed :literal:`1791577989`, also keine
unabhängigen Zufallsstichproben. Der Nachweis trennt Root-Testbytes von
ergänzter Aufräuminstrumentierung.

Der benannte `GitHub-Referenzlauf
<https://github.com/netresearch/typo3_http_guard/actions/runs/38000922527>`_
misst **0,604912 ms p95 bei einer Grenze von 2 ms**: 500 Messungen nach 30
Aufwärmungen, 64 IPv6-Adressen und 128 Profile auf Ubuntu 24.04/AMD EPYC
7763/PHP 8.5.11/Core 14.3.8/Guzzle 8.2.0, ohne DNS-, Netzwerk- oder Logzeit.
Die Merge-Referenz ist :literal:`de0807b2979360bfc5f0993a66904674214cc44d`;
der Produktionsbaum hat den Hash
:literal:`36eafea456ddf1115b56b1e059b3e8d8992b1fabc5d9fc8a49f6244b48c6d866`.
Der Caller-Sink-Nachweis der ersten nativen Suite überträgt separat 4 MiB
mit weniger als 4 MiB zusätzlichem Spitzenspeicher. Keine Messung gilt
automatisch für spätere Dateiinhalte.

Frühere Suitezahlen und Hashes gelten für ihre Quellen. Vor dem Merge
müssen CI-, Verification- und Security-Gate am unabhängig geprüften Head
bestehen. Lokaler Erfolg belegt keinen späteren GitHub-Lauf.

.. _reconciliation-alpha:

Alpha-Umfang und Veröffentlichung
================================

Für die autorisierte Alpha-Arbeit verlangt der Nutzer unabhängige
Agentenreviews, behobene Befunde und erfolgreiche einschlägige Prüfungen.
Eine zusätzliche menschliche Freigabe oder ein Betreiberpilot ist dafür
nicht erforderlich. Eine unabhängige menschliche Sicherheitsprüfung und
ein tatsächlicher repräsentativer Betreiberpilot bleiben unvollendet und
Empfehlungen für die Produktivbewertung; sie werden nicht als bestanden gezählt.

Version **0.1.1 ist auf TER und Packagist veröffentlicht**. Der ursprüngliche
Publisher veröffentlicht die Artefakte und Pakete, scheitert anschließend
aber an seiner Attestation-Prüfung. Der korrigierte Verifikationslauf besteht
gegen das unveränderte Release und ersetzt kein Asset. Siehe
:ref:`release-provenance`.

Die dokumentierten TYPO3-Intercept-Deployments :literal:`main` und
:literal:`0.1` stehen weiter auf **Awaiting Approval**. Die öffentliche
Main-Handbuchadresse liefert am 9. Oktober 404. Ein lokaler warnungsfreier
Build und enthaltene EN-/DE-Quellen belegen keine externe Freigabe.
Diese externe Veröffentlichung ist keine zusätzliche Alpha-Bedingung;
die Dokumentationsänderungen erzeugen kein neues Release.
