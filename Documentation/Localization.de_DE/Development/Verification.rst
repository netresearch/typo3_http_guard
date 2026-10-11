.. _verification-report:

Dokumentierte Prüfstände
=======================

Diese Ergebnisse gehören zu den jeweils genannten Quellständen. Neue
Produktions- oder Teständerungen benötigen eigene einschlägige Prüfungen.
Die aufgezeichneten Mengen werden keiner späteren Revision zugerechnet.
Der aktuelle Abgleich steht unter :ref:`assessment-reconciliation`.

.. _verification-integrated-local:

Integrierte lokale Prüfungen
===========================

Die integrierte Offline-Unit-Suite besteht je **1.535 Tests mit 7.199
Assertions** auf echten Core-13.4.36-/Guzzle-7.15.5- und
Core-14.3.8-/Guzzle-8.2.0-Graphen unter PHP 8.5.11. Diese lokalen
Unit-Läufe sind kein nativer Wire-Lauf oder abschließendes
Gesamtmutationsergebnis.

Abgeschlossene lokale Level-10-Prüfungen für Kern und echten Core 13/14
sowie die Architekturprüfung melden keine Fehler; Rector schlägt keine
Änderungen vor. Die aufgezeichnete Stilprüfung findet keine Änderungen in
**196 Dateien**: 143 im Extension-/Test-/Tool-Bereich und 53 im eingebetteten
MIT-Kern. Alle 173 aktuellen Unit-Eingaben bleiben bytegleich. Frühere
Scannerkommentare behalten ihren separat bestätigten identischen
ausführbaren AST und ihre historische Bytebindung.

Der vollständige Qualitätslauf und 118 Tool-Kontrollen bestehen bei
**850 Arbeitsdateien und 21.601 Vendor-Dateien** mit unveränderten Hashes
vor und nach der Ausführung. Sie entsprechen dem eingefrorenen mechanischen
Prüfstand vor Erzeugung der Abschlussberichte. Der frühere 280-Dateien-Beleg
enthält Mutation-Bootstrap, Agentenanweisungen und Forschungsquellen nicht.
Später erzeugte Berichte und Dokumentation übernehmen keine Bytegleichheit.
Spätere Korrekturen der Fixture-Prüfung und der Baseline besitzen eigene aktuelle
Prüfbelege; der 850-Dateien-Stand enthält die frühere Wächterdarstellung.

Die abschließende native Gesamtmessung erreicht **90,43 % MSI/91,54 %
Covered MSI** bei 3.794 Mutanten: 3.424 durch Tests erkannt, 293 unerkannt,
46 ohne Abdeckung, drei Fehler, vier Syntaxfehler und 24 Timeouts; keine
übersprungenen oder ignorierten Mutanten. Alle 263 ursprünglichen Eingaben
und 21.601 Vendor-Dateien bleiben vor und nach dem Lauf gleich. 262 Eingaben
entsprechen Root: 261 Arbeitsdateien und eine separat an erfasste
Vendor-Daten und aktuelle Root-Bytes gebundene Installationsmetadatei.
Die erzeugte Lockdatei gehört nur zum Prüfstand.
Gemessen wird mit PHP 8.5.10/Core 14.3.8/Guzzle 8.2.0, Standardmutatoren und
20 Sekunden Timeout bei unveränderten Zielen von 90 %/90 %. Reine Test-Kills
ergeben 90,2478 %. Das Tool zählt Fehler-/Syntaxfälle zum MSI, Timeouts bei
:literal:`--with-timeouts` nicht. Dem früheren 261-Eingaben-Lauf mit
90,46 %/91,57 % fehlen vorab erfasste Hashes beider PHP-Einstiegspunkte.
Ein abgebrochener Versuch mit 297 Eingaben besitzt keinen vollständigen
Messwert; zurückgebliebene Reports ersetzen ihn nicht.

Öffentliche Ableitungen verwenden typisierte SHA-256-Werte und nennen
Transformation, ursprünglichen Rohhash und eigenen Bytehash. Sie bewahren
den vollständigen ursprünglichen JSON-Wert und alle Mengen. Die
unveränderten Rohaufzeichnungen bleiben außerhalb des aktiven Checkouts;
die Ableitungen beanspruchen keine identischen Originalbytes.

Der frühere lokale Überarbeitungsstand besteht **340 Unit-Tests mit 2.125
Assertions** unter PHP 8.5.11. Seine Level-10-Profile für Kern/Core 14 und
die Architekturprüfung melden keine Fehler; die Stilprüfung über 158
Dateien sowie Rector schlagen keine Änderungen vor. Diese aufgezeichneten
Zahlen gelten nicht für spätere Quell- und Testergänzungen und qualifizieren
die früheren nativen Läufe nicht erneut.

Die erste native Gesamtmessung liefert 3.790 Mutanten, 66,07 % MSI und
73,47 % Covered MSI auf ihrem Stand mit 420 Eingaben. Der spätere Lauf
misst 3.794 Mutanten und 86,29 %/88,65 % bei 556 unveränderten Eingaben.
Er liegt vor den jüngsten Reporter-, DNS-, Lease- und Runner-Korrekturen.
Beide scheitern an den unveränderten Zielen von 90 %/90 %. Ein bestandener
gezielter Umfang belegt keinen bestandenen Gesamtlauf.

Die erste native Initialsuite besteht 389 Tests/3.255 Assertions; drei
Wiederholungen verwenden denselben Seed :literal:`1791577989`, also keine
unabhängigen Zufallsstichproben. Die benannte GitHub-Policy-Referenz misst
0,604912 ms p95 bei 2 ms Grenze auf ihrem eigenen Produktionsbaum.
Mengen, Fehler-/Timeout-Bedeutung und Quellgrenzen stehen unter
:ref:`assessment-reconciliation`.

.. _verification-current-core:

Gepatchte Core-Prüfstände
=======================

Die festen Framework-Fixtures verwenden Core 13.4.36 und 14.3.8.
Die folgenden Zahlen gehören zum früheren Prüfstand :literal:`e69ddad`
vor der Umstellung auf semantische Bereiche. Alle vier Composer-Zellen
haben ihren echten Bootstrap-,
Modus-, CLI- und Wire-Testlauf bestanden: 168 Prozesse, 140 Bootstrap-Prüfungen einschließlich erwarteter Guard-Ablehnungen
und 156 Offline-Nachweise ohne neue TCP- oder HTTP-Kontakte. Die beiden
aktuellen klassischen Archive bestehen separat 84 Prozesse, 70
Bootstrap-Prüfungen und 78 Offline-Nachweise. Die echte Core-Aktivierung
persistiert den Extension-Eintrag in PackageStates und erzeugt den
Class-Loading-Cache. Die damalige Unit-/Wire-Suite besteht je SDK-Kombination
145 Tests mit 2.253 Assertions unter PHP 8.5.10 und PHPUnit 11.5.57.
Historische Coverage-, Mutations- und Assessmentzahlen werden diesen
neuen Läufen nicht zugerechnet.

.. _verification-semantic-support:

Semantische Kompatibilität und aufgezeichnete Läufe
=================================================

Die Produktionsconstraints erlauben PHP :literal:`^8.2`, Core
:literal:`^13.4.36 || ^14.3.8` sowie die SDK-Bereiche aus
:ref:`installation-requirements`. Kompatible Patch- und Minor-Updates
benötigen keine neue Extension-Version. Die Laufzeitprüfungen kontrollieren
die echte Core-Elternklasse vor dem Laden der Ersatzklasse sowie die
öffentlichen SDK-Methoden und die Middleware-Struktur. Zusätzliche optionale
Konstruktorargumente bleiben kompatibel. Künftige PHP-Versionen sind damit
zulässig, aber noch nicht als tatsächlich geprüft ausgewiesen.

Die Factory über das öffentliche :literal:`CurlFactoryInterface` begrenzt
jede Lease auf ein natives Handle. Ein zweiter Aufruf wird vor der
Handle-Erzeugung durch die innere Factory abgelehnt. Die Sperre gilt auch
bei erneuter Ausführung während der Body-Vorbereitung und hängt nicht von
privaten SDK-Wiederholungszählern ab. Reguläre Middleware-Retries und
Redirects erzeugen jeweils eine neue Lease.

Der aufgezeichnete semantische Prüfstand besteht fünf vollständige
Unit-/Wire-Läufe mit jeweils **201 Tests und 2.405 Assertions** im per Digest
fixierten PHP-8.5.10-Image mit PHPUnit 11.5.57. Jeder Lauf umfasst 152
Unit-Tests mit 1.329 Assertions und 49 Integrationstests mit 1.076
Assertions, ohne Fehler oder übersprungene Tests. Die Hashes aller 108
gebundenen Produktions-, Test- und PHPUnit-Konfigurationsdateien bleiben
zwischen Beginn und Ende der Läufe identisch.

.. list-table:: Tatsächlich ausgeführte Core- und SDK-Versionen
    :header-rows: 1

    * - Lauf
      - Core
      - Guzzle / Promises / PSR-7
    * - Guzzle-7-Untergrenze
      - 13.4.36
      - 7.15.2 / 2.5.1 / 2.13.0
    * - Historischer Guzzle-7-Archivstand
      - 14.3.8
      - 7.15.3 / 2.5.2 / 2.13.0
    * - Fester Guzzle-7-Teststand
      - 14.3.8
      - 7.15.5 / 2.5.3 / 2.13.1
    * - Fester Guzzle-8-Teststand
      - 14.3.8
      - 8.2.0 / 3.0.2 / 3.1.0
    * - Separat aufgelöste Guzzle-8-Untergrenze
      - 14.3.8
      - 8.2.0 / 3.0.2 / 3.1.0

Die fünf Ausführungen prüfen vier unterschiedliche SDK-Versionstupel.
Die separate Guzzle-8-Auflösung an der Untergrenze wählt dieselben
SDK-Versionen wie der feste Guzzle-8-Teststand. Künftige kompatible
Versionen werden damit nicht als bereits ausgeführt ausgewiesen.
Die separat wiederholte Composer-Core-Matrix besteht nach der Korrektur
der Elternklassen-ABI **168 Prozesse, 140 Bootstrap-Prüfungen einschließlich erwarteter Guard-Ablehnungen und 156
Offline-Nachweise ohne neue TCP- oder HTTP-Kontakte**. Die gemeinsamen
kuratierten PHPStan-Profile für Core 13 und 14 melden keine Fehler.
Der Regressionstest für Referenzparameter der Elternklasse besteht 19 Tests
mit 31 Assertions. Mutationen der Sicherheitsuntergrenze, der Annahme
künftiger Minor-Versionen und der Wiederherstellung des Warning-Handlers
scheitern an ihren vorgesehenen Kontrollen. Die Nachweise der klassischen
Installation stehen separat in
:file:`Build/Reports/Assessment/review-loop/semantic-qualification/summary.json`;
die früheren klassischen Ergebnisse behalten ihre historische Quellbindung.
Die aktuellen Berichte und Quellbindungen liegen
im
`Nachweisverzeichnis
<https://github.com/netresearch/typo3_http_guard/tree/main/Build/Reports/Assessment/review-loop/semantic-runtime>`_.

Bei der früheren Factory-Modulübergabe laufen auf den echten Guzzle-Versionen
7.15.3, 7.15.5 und 8.2.0 jeweils
fünf Factory-Tests mit 23 Assertions unter PHP 8.5.11 und PHPUnit 11.5.57
durch. Die Kontrolle über den öffentlichen Finish-Pfad erzeugt
ohne Sperre ein zweites natives Handle; mit Sperre erfolgt die Ablehnung
vor dessen Erzeugung. Diese Tests führen keinen nativen Tick und kein
Netzwerk-I/O aus. Nach Entfernen der Sperre schlägt der gezielte Nachweis
auf beiden aktuellen Guzzle-Hauptversionen fehl. Diese Zahlen gehören
nicht zu den vollständigen Core- und Wire-Testläufen.

Drei historische SDK-Teststände und 14 feste CI-Zellen bleiben erhalten.
Die native Verifikation ergänzt einen Guzzle-7-Lauf an der Untergrenze und
vier frei aufgelöste Zellen, insgesamt acht native Zellen. Die vier Zellen
für Core 13/14 und Guzzle 7/8 lösen
bei Pull Requests und wöchentlichen CI-Läufen die neuesten kompatiblen
Graphen auf. Ihre Ergebnisse werden getrennt von historischen Zählern
dokumentiert. Lokale Prüfungen bestätigen keinen späteren Remote-Lauf
gegen einen anderen Commit.

Die vier vollständigen Dependency-Auflösungen des früheren Prüfstands
verwenden SVG-Sanitizer 1.0.0 und enthalten keine bekannten Sicherheitsmeldungen. Der Composer-
Sicherheitsblock bleibt aktiv. Core 13 benötigt weiterhin das aufgegebene
Upstream-Paket :literal:`doctrine/annotations`; die explizite Option
:literal:`--abandoned=report` erhält diesen Wartungshinweis und lässt den
Audit bei jeder Sicherheitsmeldung fehlschlagen. Core 14 besteht auch den
Standardaudit mit Exitcode 0. Historische Manifeste und Locks bleiben
bytegetreu in gekennzeichneten ZIP-Archiven erhalten und sind keine
Installationsquellen.

Die früheren statischen Prüfungen mit PHPStan 2.3.1 auf Level 8 bestehen
für die echten Core- und SDK-Konfigurationen. Die integrierten aktuellen
Level-10-Ergebnisse stehen unter :ref:`verification-integrated-local`;
die alten Level-8-Ergebnisse behalten ihre frühere Quellbindung.

.. toctree::
    :maxdepth: 1

    VerificationHistory
