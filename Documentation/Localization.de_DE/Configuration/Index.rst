.. _configuration:

=============
Konfiguration
=============

Die Policy liegt ausschließlich unter
:php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']`.
Sie wird als unveränderlicher Snapshot geladen. Änderungen an
:literal:`HTTP.allowed_hosts` oder Vaults bisherigen Hostlisten erzeugen
keine HTTP-Guard-Endpoint-Freigabe.

Die Beispiele gehören in die Projektkonfiguration, etwa
:file:`config/system/additional.php` oder bei entsprechendem klassischem
Projektlayout in :file:`typo3conf/AdditionalConfiguration.php`.
HTTP Guard bietet keinen Backend-Editor für Sicherheitsfreigaben.

.. _configuration-example:

Internes Endpoint-Beispiel
=========================

Der Beispielhost und seine Adresse sind durch die konkret genehmigte
Produktionsintegration zu ersetzen. Das Profil wirkt nur für einen Client,
den die vertrauenswürdige Serviceverdrahtung an :literal:`erp-orders` bindet.

.. literalinclude:: _Policy.php
    :language: php
    :caption: Projektkonfiguration für einen gebundenen ERP-Client

Die statische Auflösung ist optional. Ohne sie erfolgt die kontrollierte
DNS-Auflösung. Alle gefundenen IP-Adressen müssen zum erlaubten Netz passen.
Ein gültiger öffentlicher Kandidat neben einem verbotenen privaten Kandidaten
macht die Antwort nicht zulässig.

.. _configuration-schema:

Schema und Validierung
======================

Unbekannte Felder, falsche Typen, ungültige Origins und ungültige CIDRs führen
zu :literal:`configuration_invalid`. Zeichenketten wie :literal:`"5"` oder
:literal:`"true"` ersetzen keine Integer oder Boolean. :literal:`CONNECT`
ist als Endpoint-Methode verboten. Profil-IDs bestehen aus höchstens 64
ASCII-Zeichen; erlaubt sind Buchstaben, Ziffern, Punkt, Unterstrich und
Bindestrich, beginnend mit Buchstabe oder Ziffer.

Die normalisierte Policy und der SHA-256-Hash der mitgelieferten Adressregeln
bestimmen die Revision. Gleichwertige Schreibweisen werden normalisiert.
Eine geänderte Regeldatei erzeugt eine andere Revision. Zur Verwendung neuer
Revisionen müssen Registry, Engine und Clients neu erzeugt werden; daraus
folgt keine automatische Widerrufsfunktion für alte Worker.

.. toctree::
    :maxdepth: 1

    Options
    Endpoints
