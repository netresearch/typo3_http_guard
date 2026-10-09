.. _development:

=====================
Entwicklung und Tests
=====================

.. _development-layout:

Eine Extension, zwei interne Namespaces
======================================

.. list-table:: Layout des Extension-Quellverzeichnisses
    :header-rows: 1

    * - Pfad
      - Aufgabe
    * - :file:`Classes/`
      - TYPO3-Adapter unter :php:`Netresearch\NrHttpGuard`.
    * - :file:`Classes/HttpGuard/`
      - Enthaltener Sicherheitskern unter :php:`Netresearch\HttpGuard`.
    * - :file:`Configuration/`
      - TYPO3-Services, Aliases und RequestFactory-Registrierung.
    * - :file:`Resources/Private/HttpGuard/data/`
      - Runtime-Adressregeln, gemeinsamer Corpus und Quellenmetadaten.
    * - :file:`Documentation/`
      - Vollständiges Handbuch und wiederverwendbare Beispiele.
    * - :file:`Tests/Unit/`
      - Unit-Tests des TYPO3-Adapters.
    * - :file:`Tests/HttpGuard/`
      - Policy-, Transport- und echte Wire-Tests des enthaltenen Kerns.

Der Kern liest keine TYPO3-Globals und keine Vault-Secrets. Der
TYPO3-Adapter liefert Konfiguration, Registry, Resolver, Uhr und Reporter.
Die bestehende Trennung im Code ermöglicht isolierte Kernprüfungen; sie
erfordert keine zweite installierbare Library. Die Verpackungsentscheidung
steht unter :ref:`decision-single-extension`.

.. _development-unit:

Lokale Prüfungen
================

Für die Entwicklung werden die in :file:`composer.json` genannten
Dev-Werkzeuge installiert. Sie gehören nicht zur klassischen Runtime.
Der gemeinsame PHPUnit-Einstieg verwendet beide Unit-Verzeichnisse:

Die Entwicklungsconstraints erlauben PHPUnit :literal:`^11.5`, PHPStan
:literal:`^2.3` und das CI-Metapaket :literal:`^1.12`. Frühere dokumentierte
Läufe nutzten PHPUnit 11.5.57 und PHPStan 2.3.1. Für die Entwicklung wird
ein aktueller kompatibler Graph aufgelöst und mit den betroffenen Prüfungen
getestet.

Die festen Framework-Fixtures verwenden Core 13.4.36 und 14.3.8.
Die folgenden Zahlen gehören zum früheren Prüfstand :literal:`e69ddad`
vor der Umstellung auf semantische Bereiche. Alle vier Composer-Zellen
haben ihren echten Bootstrap-,
Modus-, CLI- und Wire-Testlauf bestanden: 168 Prozesse, 140 Wire-Assertions
und 156 Offline-Nachweise ohne neue TCP- oder HTTP-Kontakte. Die beiden
aktuellen klassischen Archive bestehen separat 84 Prozesse, 70
Wire-Assertions und 78 Offline-Nachweise. Die echte Core-Aktivierung
persistiert den Extension-Eintrag in PackageStates und erzeugt den
Class-Loading-Cache. Die damalige Unit-/Wire-Suite besteht je SDK-Kombination
145 Tests mit 2.253 Assertions unter PHP 8.5.10 und PHPUnit 11.5.57.
Historische Coverage-, Mutations- und Assessmentzahlen werden diesen
neuen Läufen nicht zugerechnet.

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

Der abschließend geprüfte ausführbare Quellstand besteht fünf vollständige
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
der Elternklassen-ABI **168 Prozesse, 140 Wire-Assertions und 156
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

.. code-block:: bash
    :caption: Extension und enthaltenen Kern prüfen

    vendor/bin/phpunit --configuration phpunit.xml --testsuite Unit
    vendor/bin/phpstan analyse --configuration Build/phpstan-http-guard.neon --no-progress

Diese PHPStan-Konfiguration prüft ausschließlich den enthaltenen
Sicherheitskern unter :file:`Classes/HttpGuard/` auf Level 8. Der reine
Analyse-Stub für HandlerStack ergänzt die in Guzzle 7 fehlende
Template-Deklaration; Methoden und Eigenschaften behalten ihre
SDK-Signaturen. Der Stub ist nicht im Extension-ZIP enthalten und wird
im Produktivbetrieb nicht geladen. Die Kernanalyse des früheren Prüfstands
mit PHPStan 2.3.1 besteht für alle drei SDK-Kombinationen ohne Fehlerunterdrückung und
ohne Baseline.
Auch die vier echten Core-/SDK-Integrationskonfigurationen bestehen die
damalige PHPStan-Prüfung auf Level 8 ohne Fehler.

Die Wire-Tests benötigen kontrollierte Fixtures und Zielzähler und werden
nicht als gewöhnliche Offline-Unit-Tests ausgeführt. Für eine isolierte
Policyprüfung mit einem minimalen qualifizierten SDK-Vendor kann der
separate Kernel-Bootstrap verwendet werden:

.. code-block:: bash
    :caption: Isolierte Policytests ohne TYPO3-Abhängigkeit

    HTTP_GUARD_TEST_AUTOLOAD=/absolute/sdk/vendor/autoload.php \
        /absolute/sdk/vendor/bin/phpunit \
        --configuration Build/phpunit-http-guard.xml \
        Tests/HttpGuard/Unit/Policy

Der Kernel-Bootstrap lädt ausschließlich die gewählten Dependencies und
die Produktions-/Test-Namespaces dieses Pakets. Die zusätzliche
:file:`Build/phpstan-http-guard.neon` prüft
:file:`Classes/HttpGuard/` auf PHPStan-Level 8.

.. _development-evidence:

Nachweise und Wiederholung
=========================

Die technische Prüfung unterscheidet Policyergebnisse, native
Transferkonstruktion sowie tatsächlich neue TCP- und HTTP-Kontakte.
Ein Mock-Handler allein bestätigt kein Pinning. Die Wire-Fixtures verwenden
eigene synthetische Adressen und DNS-Antworten; fremde Produktivziele sind
keine Testvoraussetzung.

Die vollständigen Reproduktionsrunner, Dependency-Locks, Containerstände,
Laufzeitdaten, Quellenhashes, JUnit-Ausgaben und die Zuordnung der originalen
45 Anforderungen zu 84 Tests liegen im **optionalen Quell-/Nachweispaket**
unter :file:`verification/` und :file:`evidence/`. Sie sind keine
Runtime-Abhängigkeit des TER-ZIP. Die dortige Datei
:file:`verification/requirements-and-tests.md` erklärt je Anforderung den
tatsächlich geprüften Umfang und offene Freigabeschritte.

Die Prüfungen umfassen insbesondere:

* IPv4-/IPv6-CIDR-Grenzen, mapped IPv6, Metadaten und enge Endpoint-Freigaben.
* Vollständige DNS-Ketten, TC-Wiederholung über TCP, Limits, NSS-Ausschluss
  und Memo-/Ablaufprüfung.
* Tatsächliches cURL-Pinning, fehlende Fähigkeiten, Optionen, Proxy-SAPI,
  Redirects, Retries, Streaming und Cancel-Lebensdauer.
* Echte TYPO3-DI-/RequestFactory-Bootstraps, Kontextrestriktionen, CLI und
  klassische Archive ohne separat installierte Library.
* Optionale Vault-Migration mit unabhängigen Resource-/OAuth-Bindungen.
* Isolierte Mutanten für entfernte Sperren und Pin-/Fallback-Verletzungen;
  die Zeugen prüfen tatsächliche Kontakte vor den Assertions.

Die PHP-/SDK-Kernmatrix ist von der Frameworkmatrix getrennt. Ein Kernlauf
unter PHP 8.2 qualifiziert nicht automatisch jede TYPO3-Version unter
diesem PHP. Kompatible Patch- und Minor-Updates bleiben innerhalb der
unterstützten Bereiche installierbar. Änderungen an Hauptversionen,
Untergrenzen, Handlern, Protokollen oder erlaubten Optionen benötigen
Quellenvergleich und die einschlägigen Policy-, Wire-, Integrations- und
Mutationstests.

Die historische Mikrobenchmark läuft auf dem aufgezeichneten lokalen Host;
sie erfüllt nicht die noch offene Messung auf der im Plan verlangten
benannten CI-Referenz. Ein vollständiger technischer Testlauf ersetzt
weder die unabhängige menschliche Sicherheitsprüfung noch den konkreten
Betreiberpilot.

.. _development-docs:

Handbuch rendern
===============

Die Quellen unter :file:`Documentation/` verwenden die aktuelle
phpDocumentor-Guides-Konfiguration. :file:`Settings.cfg` wird nicht benötigt.
Die offizielle TYPO3-Dokumentation beschreibt den
`Rendering-Container
<https://docs.typo3.org/m/typo3/docs-how-to-document/main/en-us/Howto/RenderingDocs/Index.html>`_.

.. code-block:: bash
    :caption: Offizieller Renderer im Extension-Verzeichnis

    docker run --rm -v "$PWD":/project \
        ghcr.io/typo3-documentation/render-guides@sha256:fcf1ea87377ac401ce595b8c320c03b2b1bf2505ec0109561ca2fe56d7d71fc1 \
        --config=Documentation --no-progress --fail-on-log

Die HTML-Ausgabe wird unter :file:`Documentation-GENERATED-temp/` erzeugt.
Bei einer eigenen :literal:`--output`-Option muss der Zielpfad innerhalb des
gemounteten Verzeichnisses liegen, damit die Dateien erhalten bleiben.
Der Renderer muss mit einer Warnungen berücksichtigenden Option ausgeführt
werden; ein bloßer Exitcode einer ungeprüften Standardausführung ist kein
Nachweis fehlerfreier Verweise.

Der Digest bezeichnet den Renderer des dokumentierten warnungsfreien
englischen und deutschen Builds. Nach einer Aktualisierung müssen beide
Sprachen erneut gerendert werden.

.. toctree::
    :maxdepth: 1

    Licenses
