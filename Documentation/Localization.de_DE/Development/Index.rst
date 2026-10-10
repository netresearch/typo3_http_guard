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
    * - :file:`Tests/Functional/`
      - Echte Core-Dekoration, klassische Aktivierung und Modus-Bootstraps.
    * - :file:`Tests/HttpGuard/`
      - Policy-, Transport- und echte Wire-Tests des enthaltenen Kerns.

Der Adapter liefert Konfiguration, Registry, Resolver, Uhr und Reporter.
Der unabhängige eingebettete Kern ermöglicht isolierte Tests innerhalb
dieser einen installierbaren Extension; siehe :ref:`decision-single-extension`.

.. _development-unit:

Lokale Prüfungen
================

Die Entwicklungswerkzeuge werden mit :literal:`composer install` unter
:file:`.Build/vendor/` installiert. PHPUnit :literal:`^11.5`, PHPStan
:literal:`^2.3` und :literal:`netresearch/typo3-ci-workflows:^1.12` stellen
die gemeinsam gepflegten Werkzeuge bereit. Sie gehören nicht zur
klassischen Extension-Runtime.

.. code-block:: bash
    :caption: Werkzeuge installieren und lokale Prüfungen ausführen

    composer install
    composer check:harness
    composer check:secrets
    composer check:local

CaptainHook installiert Commit- und Pre-Commit-Prüfungen. Secrets im Index
werden vor Qualität geprüft; fehlende Werkzeuge und fehlgeschlagene Checks
führen zum Fehler. Installation allein belegt keinen bestandenen Lauf.
CI bleibt Mergeinstanz; :file:`CONTRIBUTING.md` beschreibt isolierte Checkouts.

.. list-table:: Qualitätsprüfungen über Composer
    :header-rows: 1

    * - Kommando
      - Umfang
    * - :literal:`ci:test:php:cgl`
      - PHP-Stilprüfung ohne Änderungen.
    * - :literal:`ci:test:php:rector`
      - Modernisierung für die PHP-8.2-Untergrenze, ohne Änderungen.
    * - :literal:`ci:test:php:phpstan`
      - Kuratierte Kern- und echte Core-Profile, konfiguriert auf Level 10.
    * - :literal:`ci:test:php:unit`
      - Offline-Unit-Tests für Adapter und Kern.
    * - :literal:`ci:test:php:functional`
      - Native Tests gegen kontrollierte Ziele.
    * - :literal:`ci:test:php:architecture`
      - Dokumentierte Architekturgrenzen.
    * - :literal:`ci:test:php:mutation`
      - Infection über alle Quellen, einschließlich nicht abgedeckter Mutanten.
    * - :literal:`ci:test:php:fuzz`
      - Deterministische Eingabe- und Eigenschaftsprüfungen.
    * - :literal:`ci:test:php:performance`
      - Benchmark für Normalisierung, Klassifizierung und Policy.

Die kuratierten Statikregeln verwenden keine Baseline oder pauschale
Unterdrückung. Reine HandlerStack-/Client-Analysestubs erhalten echte
SDK-Methoden und Storage, ergänzen Guzzles fehlendes Template und die offenen
Konstruktor-/:literal:`getConfig`-Verträge. Native Kontrollen erhalten
Aufruferdefaults; die Stubs laufen nie in Produktion.

Die integrierte Offline-Suite besteht je **1.535 Unit-Tests mit 7.199
Assertions** auf echten Core-13-/Guzzle-7- und Core-14-/Guzzle-8-Graphen unter
PHP 8.5.11. Level 10 für Kern/Core 13/14, Architektur und Rector sind sauber.
Die aufgezeichnete Stilprüfung meldet keine Änderungen in 196 Dateien der
GPL-Extension-/Test-/Tool- und MIT-Kernbereiche. Die Gesamtmessung mit
263 Eingaben erreicht 90,43 %/91,54 % ohne übersprungene/ignorierte Mutanten;
gezielte Ergebnisse behalten eigene Bindungen. Vollständige Qualitätsbelege
und die unveränderten Ziele stehen unter :ref:`verification-integrated-local`.

Wire-Tests benötigen eigene Ziele und TCP-/HTTP-Zähler. Läufe mit denselben
Zählern müssen nacheinander ausgeführt werden. Die gewöhnlichen Unit-Tests
bleiben offline und benötigen keine Datenbank. Feste Fixtures bewahren
exakte Quellstände; vier frei aufgelöste Core-13/14- und Guzzle-7/8-Zellen
prüfen kompatible Updates bei Pull Requests und wöchentlich. Die acht
nativen Zellen und 14 festen PHP-CI-Zellen sind getrennte Matrizen.

Ausgeführte Versionen und historische Mengen stehen unter
:ref:`verification-report`. Die aktuellen Maßnahmen und ihre Nachweise
stehen unter :ref:`assessment-reconciliation`.

.. _development-functional-routes:

Functional-Routen mit echten Fixtures
=====================================

Die drei ausgeführten Core-Einstiegspunkte liegen unter
:file:`Tests/Functional/`. Die Fixture muss bereits mit echten Paketen
installiert sein; auch die kontrollierten Wire-Ziele werden separat vorbereitet.

.. code-block:: bash
    :caption: Core-Routen auf dem Host und im ausgewählten Container

    bash Build/Scripts/runTests.sh -s integration -f .Build/fixtures/core14g8
    bash Build/Scripts/runTests.sh -s classic -f .Build/classic-sites/classic14 -- active
    bash Build/Scripts/runTests.sh -s mode -f .Build/fixtures/core14g8 -- observe
    bash Build/Scripts/runTests.sh -s integration -f .Build/fixtures/core14g8 -p 8.5 -t 14

Ohne Laufzeitauswahl verwendet der Wrapper das Host-PHP. Ausgewählte Container
laufen über den offiziellen :literal:`suite_http_guard_functional`-Hook mit
dem per Digest festgelegten Image. Kurzlebige Container verwenden das
Host-Netzwerk, um die vorbereiteten Zeugen dieser Einstiegspunkte zu erreichen.
Sie mounten das physisch geprüfte Projekt und reichen Fixture-Argumente
unverändert weiter. Vor dem Test prüft der Runner den tatsächlichen PHP-/Core-
Graphen der Fixture. Externe Fixtures und herausführende Autoloader-Symlinks
werden vor der Übergabe abgelehnt. Der Fixture-Graph bleibt unverändert;
die übrigen Suites behalten ihre bisherigen gemeinsamen Routen.

Container laufen als Aufrufer ohne Capabilities und mit schreibgeschütztem
Root-Dateisystem. Bereinigung entfernt eigene erfasste IDs; Fehler werden
weitergegeben. Fehlt :literal:`composer/semver` in Core 13, lädt der Prüfer
nur dessen Namespace aus Entwicklungswerkzeugen, mit dem Core-/SDK-Graphen
der Fixture.

Die 27 Offline-Kontrollen prüfen Argumente, Pfadgrenzen, Ressourcenbesitz und
Fehlerweitergabe. Separat bestanden je 20 echte Functional-Routen auf dem Host
mit PHP 8.5.11 und im Container mit PHP 8.5.10. Die Nachweise sind an die
ausgeführten Quellen und Graphen gebunden. Einzelheiten stehen unter
:file:`Build/Fixtures/README.md`.

.. _development-mutation-scope:

Mutationsumfang in CI
====================

Bei Pull Requests werden seit dem validierten Basiscommit hinzugefügte,
geänderte oder umbenannte produktive PHP-Dateien ausgewählt. Die mit NUL
getrennte Git-Dateiliste wird geprüft und als positionale Pfade an Infection
übergeben. Die vollständigen Dateien einschließlich unveränderter Funktionen
werden mutiert, direkt unter :file:`Classes/` und in Unterverzeichnissen.
MSI und Covered MSI behalten jeweils 90 %; Standardmutatoren, nicht
abgedeckte Mutanten und gemessene Fehler bleiben im Prüfumfang.

Ohne hinzugefügte, geänderte oder umbenannte produktive PHP-Dateien wird die
Mutationsmessung ausdrücklich als übersprungen dokumentiert. Unit-Tests und
native Verhaltensprüfungen laufen weiterhin; daraus ergibt sich kein
Mutationswert. Zeitgesteuerte Läufe, Pushes auf :literal:`main` und manuelle
Aufrufe prüfen alle :file:`Classes/` ohne Auswahl geänderter Dateien. Die vollständige
Alpha-Qualifikation erhält einen separaten Quellenbezug und den tatsächlich
gemessenen Wert. Eine konfigurierte Grenze belegt keinen bestandenen Lauf.

.. _development-evidence:

Nachweise und Wiederholung
=========================

Die technische Prüfung unterscheidet Policyergebnisse, native
Transferkonstruktion sowie tatsächlich neue TCP- und HTTP-Kontakte.
Ein Mock-Handler allein bestätigt kein Pinning. Die Wire-Fixtures verwenden
eigene synthetische Adressen und DNS-Antworten; fremde Produktivziele sind
keine Testvoraussetzung.

Gepflegte Runner und Fixtures liegen unter :file:`Tests/` und :file:`Build/`,
quellgebundene Berichte unter :file:`Build/Reports/Assessment/`.
:ref:`development-requirements` ordnet die originalen 45 Anforderungen,
84 Szenarien und acht Invarianten heutigen Tests und unveränderter Historie
zu. :ref:`verification-report` trennt aktuelle Läufe von alten Messungen;
diese Entwicklungsnachweise sind keine Runtime-Abhängigkeiten des TER-ZIP.

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

Die benannte Policy-Benchmark und historische Messungen behalten ihre
Quell-/Laufzeitgrenzen unter :ref:`assessment-reconciliation`.

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

HTML liegt unter :file:`Documentation-GENERATED-temp/`; ein eigenes Ziel
muss im gemounteten Verzeichnis bleiben. :literal:`--fail-on-log` behandelt
Warnungen als Fehler; ein Standard-Exitcode belegt keine gültigen Verweise.
Dieser Digest bezeichnet den aufgezeichneten Renderer. Ein Update benötigt
frisches englisches und deutsches Rendering vor einer neuen Erfolgsangabe.

.. toctree::
    :maxdepth: 1

    Licenses
    Requirements
    Verification
    Dependencies
    Assessment
    Reconciliation
    ReleaseProvenance
