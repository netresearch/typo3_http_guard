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

.. code-block:: bash
    :caption: Extension und enthaltenen Kern prüfen

    vendor/bin/phpunit --configuration phpunit.xml --testsuite Unit
    vendor/bin/phpstan analyse --configuration Build/phpstan-http-guard.neon --no-progress

Diese PHPStan-Konfiguration prüft ausschließlich den enthaltenen
Sicherheitskern unter :file:`Classes/HttpGuard/` auf Level 8.

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
diesem PHP. Neue Core-/SDK-Versionen, Handler, Protokolle oder erlaubte
Optionen benötigen Quellenvergleich und Wiederholung der einschlägigen
Policy-, Wire-, Integrations- und Mutationstests.

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
<https://docs.typo3.org/permalink/h2document:rendering-container>`_.

.. code-block:: bash
    :caption: Offizieller Renderer im Extension-Verzeichnis

    docker run --rm -v "$PWD":/project \
        ghcr.io/typo3-documentation/render-guides:latest \
        --config=Documentation --no-progress --fail-on-log

Die HTML-Ausgabe wird unter :file:`Documentation-GENERATED-temp/` erzeugt.
Bei einer eigenen :literal:`--output`-Option muss der Zielpfad innerhalb des
gemounteten Verzeichnisses liegen, damit die Dateien erhalten bleiben.
Der Renderer muss mit einer Warnungen berücksichtigenden Option ausgeführt
werden; ein bloßer Exitcode einer ungeprüften Standardausführung ist kein
Nachweis fehlerfreier Verweise.

.. toctree::
    :maxdepth: 1

    Licenses
