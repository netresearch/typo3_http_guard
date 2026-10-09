.. _start:

.. _http-guard:

==========
HTTP Guard
==========

HTTP Guard kontrolliert ausgehende HTTP-Anfragen im registrierten
TYPO3-RequestFactory-Pfad. Der Transport prüft die Zieladresse vor jedem
Verbindungsversuch und bindet die Verbindung an die geprüften IP-Adressen.
Interne Ziele benötigen zusätzlich einen ausdrücklich gebundenen Client.

Die Version 0.1.0 ist eine Alpha-Version. Sie enthält den Sicherheitskern,
den TYPO3-Adapter, die Adressregeln und dieses Handbuch in **einer** Extension
mit dem Schlüssel :literal:`nr_http_guard`. Für eine klassische Installation
ist kein Composer-Aufruf und kein zusätzliches HTTP-Guard-Paket erforderlich.

.. _http-guard-start:

Einstieg
========

* :ref:`installation`: Voraussetzungen und Installation mit Composer oder ZIP.
* :ref:`configuration`: Alle Policyfelder und ein internes Endpoint-Beispiel.
* :ref:`api`: Gebundene PSR-18-Clients und Public Fetch im eigenen Projekt.
* :ref:`operations`: Diagnose, Einführung, Änderungen und Rollback.
* :ref:`security`: DNS, Transport, Redirects und Grenzen der Abdeckung.

Ohne eigene Konfiguration gilt :literal:`enforce`. Private Adressen, Loopback,
Metadatenziele und besondere Netze sind dann für gewöhnliche öffentliche
Anfragen gesperrt. Ein Endpoint-Profil allein erteilt einem gewöhnlichen
RequestFactory-Aufruf keine zusätzliche Berechtigung.

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
    Decisions/Index

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
die globale Extension schützt Vault nicht automatisch. Der erforderliche
Adapter und seine Nachweise werden im zusätzlichen Quell- und Nachweispaket
geliefert. Für die Installation und den Betrieb der Extension genügt dieses
Handbuch.
