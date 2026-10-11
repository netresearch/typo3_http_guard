.. _start:

.. _http-guard:

==========
HTTP Guard
==========

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

Die Version 0.1.1 ist eine Alpha-Version. Sie enthält den Sicherheitskern,
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

* :ref:`installation`: Voraussetzungen und Installation mit Composer oder ZIP.
* :ref:`configuration`: Alle Policyfelder und ein internes Endpoint-Beispiel.
* :ref:`api`: Gebundene PSR-18-Clients und Public Fetch im eigenen Projekt.
* :ref:`operations`: Diagnose, Einführung, Änderungen und Rollback.
* :ref:`security`: DNS, Transport, Redirects und Grenzen der Abdeckung.

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
