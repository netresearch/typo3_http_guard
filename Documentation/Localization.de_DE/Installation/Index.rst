.. _installation:

============
Installation
============

.. _installation-requirements:

Voraussetzungen
===============

Composer unterstützt TYPO3 :literal:`^13.4.36 || ^14.3.8` und PHP
:literal:`^8.2`. PHP muss zusätzlich die Anforderungen des eingesetzten
Core erfüllen. Kompatible Updates innerhalb dieser Bereiche benötigen
keine neue HTTP-Guard-Version. Die Laufzeitprüfung kontrolliert die echte
Core-Elternklasse und die SDK-Fähigkeiten; inkompatible APIs werden vor
einem nativen Sendeversuch abgelehnt.

.. list-table:: Unterstützte Versionsbereiche des Transports
    :header-rows: 1

    * - Guzzle
      - Promises
      - PSR-7
    * - :literal:`^7.15.2`
      - :literal:`^2.5.1`
      - :literal:`^2.13.0`
    * - :literal:`^8.2`
      - :literal:`^3.0.2`
      - :literal:`^3.1`

Diese Untergrenzen stellen die benötigten Transport-APIs bereit. Composer
muss einen kompatiblen vollständigen Dependency-Graph innerhalb der
unterstützten Hauptversionen auflösen. Jede Transfer-Lease darf höchstens
ein natives Handle erzeugen. Verdeckte SDK-Wiederholungen können die
Policyprüfung pro Versuch dadurch nicht umgehen.

Die festen Core-/SDK-Fixtures dokumentieren reproduzierbare Prüfungen mit
konkreten Versionen. Sie begrenzen nicht die installierbaren Dependencies.
Die aktuellen Kompatibilitätsprüfungen stehen unter :ref:`development-unit`.
Die historische Kernmatrix deckt PHP 8.2–8.5 ab; künftig kompatible
PHP-Versionen sind erlaubt, aber damit noch nicht als geprüft ausgewiesen.
Die klassischen Extension-Metadaten erlauben TYPO3 13.4.36–14.3.99 und PHP
8.2.0–8.99.99. Die klassische TYPO3-Obergrenze ist enger als die
Composer-Bereiche und bezieht sich derzeit auf die LTS-Zweige 13.4/14.3.
Die zusammenhängende TER-Spanne kann die getrennte Core-14-Untergrenze
nicht ausdrücken: Die Laufzeitprüfung lehnt Core 14.0–14.2 sowie
14.3-Patches vor 14.3.8 ab. Die Core-Anforderungen an PHP und dieselben
Laufzeitprüfungen gelten auch dort.

Der kontrollierte Transport benötigt :literal:`ext-curl`, die Funktion
:literal:`curl_multi_exec` und funktional mindestens libcurl 7.59.0. Die
libcurl-Untergrenze bestätigt keinen Sicherheitsstand. Betriebssystem- und
PHP-Sicherheitsupdates sind nach den Hinweisen des jeweiligen Herstellers
zu installieren. Der Prozess benötigt Zugriff auf seine kontrollierten
DNS-Server oder ausdrücklich konfigurierte statische Hosteinträge.

.. warning::
    Prozessvariablen für HTTP-, HTTPS-, ALL- oder NO_PROXY werden unabhängig
    von ihrer Groß-/Kleinschreibung konservativ abgelehnt. Dies gilt auch für
    :literal:`NO_PROXY=*`. Ein Unternehmensproxy benötigt einen gesondert
    qualifizierten Adapter; diese Version enthält keinen Proxybetrieb.

.. _installation-composer:

Mit Composer
============

Die Lieferung enthält die Extension als vollständiges Quellverzeichnis.
Sie muss vor einer Veröffentlichung nicht aus Packagist verfügbar sein.
Für eine lokale Installation wird sie beispielsweise nach
:file:`packages/nr_http_guard/` im TYPO3-Projekt kopiert. Eine lokale
Path-Repository-Konfiguration kann im vorhandenen Projekt ergänzt werden:

.. code-block:: json
    :caption: Ergänzung der Projekt-composer.json

    {
        "repositories": {
            "nr-http-guard-local": {
                "type": "path",
                "url": "packages/nr_http_guard",
                "options": {
                    "symlink": false,
                    "versions": {"netresearch/nr-http-guard": "0.1.0"}
                }
            }
        }
    }

Anschließend wird nur das eine Extension-Paket installiert:

.. code-block:: bash
    :caption: Installation im TYPO3-Projekt

    composer require netresearch/nr-http-guard:0.1.0 --with-all-dependencies
    vendor/bin/typo3 cache:flush
    vendor/bin/typo3 http-guard:config-check
    vendor/bin/typo3 http-guard:doctor

Die Beispiele setzen einen vom Projekt freigegebenen Dependency-Lock voraus.
Die Auflösung ist vor dem Deployment mit der Tabelle abzugleichen. Die
Extension benötigt kein zusätzliches :literal:`netresearch/http-guard`.

.. _installation-classic:

Klassisch ohne Composer
======================

Das Archiv :file:`nr_http_guard_0.1.0.zip` enthält die Extension-Dateien
direkt auf der Archivebene. Es enthält weder einen übergeordneten
Projektordner noch ein zusätzliches Vendor-Verzeichnis. Die offizielle
TYPO3-Installation stellt Guzzle, PSR-Komponenten und Symfony bereit.

1. Eine unterstützte klassische TYPO3-Version installieren oder bereitstellen.
   Die geprüften offiziellen Archive 13.4.36 und 14.3.8 enthalten Guzzle
   8.2.0, Promises 3.0.2 und PSR-7 3.1.0 innerhalb der erlaubten Bereiche.
2. Das lokale ZIP im Extension Manager über die Upload-Funktion importieren.
   Wenn die Hosting-Umgebung diesen Weg nicht anbietet, das Archiv vollständig
   nach :file:`typo3conf/ext/nr_http_guard/` entpacken und die Extension im
   Extension Manager aktivieren.
3. System- und Dependency-Injection-Caches neu aufbauen. Das mitgelieferte
   :file:`composer.json` bleibt dabei erhalten: TYPO3 benötigt seine Metadaten
   auch ohne eine Composer-Projektinstallation und registriert beide
   enthaltenen PHP-Namespaces selbst.
4. Die Policy in der Projektkonfiguration setzen und die Diagnose ausführen.
   Für klassische Projekte lautet der CLI-Einstieg im Regelfall
   :file:`typo3/sysext/core/bin/typo3`. Der Pfad hängt von der Core-Verknüpfung
   des Projekts ab.

.. code-block:: bash
    :caption: Diagnose einer klassischen Installation

    php typo3/sysext/core/bin/typo3 cache:flush
    php typo3/sysext/core/bin/typo3 http-guard:config-check
    php typo3/sysext/core/bin/typo3 http-guard:doctor

Dieses Archiv ist zur lokalen Installation vorgesehen. Eine Veröffentlichung
im TYPO3 Extension Repository ist ein gesonderter Vorgang; die Lieferung
behauptet keine bereits erfolgte TER-Veröffentlichung.

Die offiziellen Anleitungen erläutern die
`klassische TYPO3-Installation mit Archiven
<https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/Administration/Installation/ClassicMode/TarballZip.html>`_
und das auch klassisch benötigte
`composer.json einer Extension
<https://docs.typo3.org/permalink/t3coreapi:ext-composer-json-classic-compatible>`_.

.. _installation-check:

Installation prüfen
===================

:literal:`config-check` prüft die Schemafelder und meldet die Policyrevision.
:literal:`doctor` prüft den Modus, die Registry, Core-/SDK-Versionen,
cURL-Fähigkeiten und Proxyvariablen. Beide Befehle senden kein Ziel-HTTP.
Exitcode 0 von :literal:`doctor` bestätigt den unterstützten geschützten
Modus. Exitcode 2 kennzeichnet einen ausdrücklich ungeschützten Modus;
Exitcode 3 eine ungültige Konfiguration oder eine nicht unterstützte Fähigkeit.
Details stehen unter :ref:`operations-diagnostics`.

Nach Aktivierung ist zunächst ein kontrolliertes öffentliches Ziel zu prüfen,
dann ein verbotenes internes Ziel mit unveränderten TCP-/HTTP-Zählern am Ziel.
Die Projektprüfung muss den tatsächlich verwendeten RequestFactory- oder
gebundenen Client aufrufen. Ein erfolgreicher Diagnosebefehl beweist keinen
beliebigen SDK-Pfad.

.. _installation-update:

Updates und Deinstallation
=========================

Der Dependency-Lock des Projekts wird innerhalb der unterstützten Bereiche
aktualisiert. Ein kompatibles Patch- oder Minor-Update benötigt keine neue
Extension-Version. Änderungen an Extension und Adressregeln werden geprüft,
wenn diese Komponenten aktualisiert werden.
Nach jeder Policy- oder Paketänderung sind Caches neu aufzubauen und
langlebige Worker neu zu starten. Bereits erzeugte Clients behalten ihren
unveränderlichen Konfigurationsstand bis zum Austausch.

Vor einer Deinstallation müssen gebundene Clients und Service-Aliases im
Projekt angepasst werden. Nach Deaktivierung entfällt die zusätzliche
Kontrolle des TYPO3-HTTP-Pfads. Deployment- und Rollback-Schritte stehen unter
:ref:`operations-rollout` und :ref:`operations-rollback`.
