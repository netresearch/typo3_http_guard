.. _start:

.. _http-guard:

==========
HTTP Guard
==========

.. image:: /Images/netresearch-underline.svg
    :alt:
    :width: 200px
    :class: no-border

Nach Installation und Aktivierung schützt HTTP Guard den standardmäßigen
HTTP-Client von TYPO3, :php:`RequestFactory`, vor Server-Side Request Forgery
(SSRF). Bestehende Aufrufe über die registrierte Factory werden automatisch
geprüft. Dafür sind weder Änderungen an der Anwendung noch eine eigene
Policy-Konfiguration erforderlich.

Wenn die Anwendung URLs aus Benutzereingaben, Importen oder externen Daten
abruft, sperrt der Standardmodus :literal:`enforce` private Adressen,
Loopback, Cloud-Metadatenziele und besondere Netze vor dem Verbindungsaufbau.
Auch DNS-Ergebnisse und Redirects werden geprüft. Erlaubte Verbindungen
werden an die geprüften IP-Adressen gebunden.

Die Version 0.1.4 ist eine Alpha-Version. Sie enthält den Sicherheitskern,
den TYPO3-Adapter, die Adressregeln und dieses Handbuch in **einer** Extension
mit dem Schlüssel :literal:`nr_http_guard`. Für eine klassische Installation
ist kein Composer-Aufruf und kein zusätzliches HTTP-Guard-Paket erforderlich.

.. _http-guard-start:

Installieren und aktivieren
===========================

Im Composer-Projekt die veröffentlichte 0.1-Reihe installieren und die
Caches neu aufbauen:

.. code-block:: bash
    :caption: Installation im TYPO3-Projekt

    composer require netresearch/nr-http-guard:^0.1
    vendor/bin/typo3 cache:flush

Composer registriert die Extension automatisch. Bei einer klassischen
Installation :literal:`nr_http_guard` aus dem
`TER <https://extensions.typo3.org/extension/nr_http_guard>`_ installieren.
Anschließend die Extension im Extension
Manager aktivieren und die Caches neu aufbauen. Der Schutz ist ohne
Endpoint-Konfiguration aktiv. Voraussetzungen und Diagnose stehen unter
:ref:`installation`.

Interne Integrationen benötigen ein Endpoint-Profil und einen Client, der
ausdrücklich an dieses Profil gebunden ist. Ein Profil allein erlaubt
gewöhnlichen RequestFactory-Aufrufen keinen Zugriff auf interne Ziele.

.. _http-guard-next:

Weitere Schritte
================

.. card-grid::
    :columns: 1
    :columns-md: 2
    :gap: 4
    :card-height: 100

    .. card:: 📦 Installation

        Voraussetzungen prüfen und mit Composer oder aus dem TER installieren.

        .. card-footer:: :ref:`HTTP Guard installieren <installation>`
            :button-style: btn btn-primary stretched-link

    .. card:: ⚙️ Konfiguration

        Policyfelder kennenlernen und Profile für interne Endpoints einrichten.

        .. card-footer:: :ref:`Policies konfigurieren <configuration>`
            :button-style: btn btn-secondary stretched-link

    .. card:: 🚀 Client-APIs

        Gebundene PSR-18-Clients und Public Fetch im eigenen Projekt verwenden.

        .. card-footer:: :ref:`APIs kennenlernen <api>`
            :button-style: btn btn-secondary stretched-link

    .. card:: 🛠️ Betrieb

        Anfragen diagnostizieren und Einführung, Änderungen und Rollback planen.

        .. card-footer:: :ref:`HTTP Guard betreiben <operations>`
            :button-style: btn btn-secondary stretched-link

    .. card:: 🔒 Sicherheit

        DNS-, Transport- und Redirectprüfungen sowie die Grenzen der Abdeckung verstehen.

        .. card-footer:: :ref:`Sicherheitsgrenzen nachlesen <security>`
            :button-style: btn btn-secondary stretched-link

    .. card:: 💻 Entwicklung

        Entwicklungsumgebung einrichten und die Prüfwerkzeuge ausführen.

        .. card-footer:: :ref:`An HTTP Guard mitarbeiten <development>`
            :button-style: btn btn-secondary stretched-link

.. _http-guard-manual:

Handbuch
========

.. toctree::
    :maxdepth: 2

    Installation/Index
    Configuration/Index
    Api/Index
    Operations/Index
    Security/Index
    Development/Index
    Adr/Index
    Changelog

.. _http-guard-scope:

Geltungsbereich
===============

Die Extension ersetzt keine Netzwerk-Firewall. Sie kontrolliert den
dokumentierten TYPO3-HTTP-Pfad nach ihrer Registrierung und die von ihren
Factories erzeugten Clients. Eingehende PSR-15-Middleware, frühe
Bootstrap-Anfragen, fremde SDK-Clients, direkte cURL-Aufrufe und eigene
Socketverbindungen benötigen eine separate Integration. Die vollständige
Abgrenzung steht unter :ref:`security-coverage`.

Die optionale nr-vault-Anpassung gehört zu einer gesonderten Migration;
die globale Extension schützt Vault nicht automatisch. Das API-Kapitel
verlinkt den aufgezeichneten Referenzpatch und seine historischen Nachweise
unter :ref:`api-vault`. Für Installation und Betrieb genügt dieses Handbuch.

.. card-grid::
    :columns: 1

    .. card:: [n] Eine Extension von Netresearch

        .. image:: /Images/netresearch-underline.svg
            :alt:
            :width: 120px
            :class: no-border

        Professionelle TYPO3-Entwicklung, Integration und Beratung
        von der Netresearch DTT GmbH.

        .. card-footer:: `netresearch.de <https://www.netresearch.de>`__
            :button-style: btn btn-secondary stretched-link
