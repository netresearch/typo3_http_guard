.. _configuration-options:

===================
Allgemeine Optionen
===================

Alle Werte sind relativ zu :literal:`EXTCONF.nr_http_guard` angegeben.
Fehlende Felder erhalten die unten genannten Defaults.

.. _configuration-mode:

Modus und Adressen
==================

.. confval:: schemaVersion
    :type: integer
    :default: 1

    Nur Schema 1 wird akzeptiert.

.. confval:: mode
    :type: string
    :default: 'enforce'

    :literal:`enforce` kontrolliert den Transport und blockiert Ablehnungen.
    :literal:`observe` bewertet und protokolliert, blockiert jedoch keine
    zusätzlichen Anfragen und liefert keine überprüfte Transportbindung.
    :literal:`disabled` deaktiviert die zusätzliche Kontrolle. Der Modus
    wechselt bei Fehlern niemals automatisch. Bestehende Core-/Vault-Prüfungen
    bleiben im jeweiligen Adapter wirksam.

.. confval:: deniedCidrs
    :type: list<string>
    :default: []

    Zusätzliche Betreiber-Sperren für IPv4 oder IPv6. Diese Netze werden auch
    mit gültigem Endpoint-Profil nicht freigegeben. Ein leerer Wert entfernt
    keine mitgelieferte Sperre. IPv4-mapped IPv6-CIDRs werden nicht akzeptiert;
    dafür ist die entsprechende IPv4-CIDR zu konfigurieren.

.. confval:: endpoints
    :type: map<string, array>
    :default: []

    Höchstens 128 benannte Profile. Sämtliche Profilfelder stehen unter
    :ref:`configuration-endpoints`.

.. _configuration-resolver:

Resolver
========

.. confval:: resolver.staticHosts
    :type: map<string, list<string>>
    :default: []

    Exakte kanonische Hostnamen mit vollständiger, nicht leerer IP-Liste.
    Keine Wildcards, IP-Hosts als Schlüssel oder Betriebssystem-Suchdomänen.
    Statische Antworten passieren dieselbe Adress- und Endpoint-Prüfung wie
    DNS-Antworten. Ein statischer Eintrag erteilt keine eigene Freigabe.

.. confval:: resolver.cacheTtlSeconds
    :type: integer
    :default: 5

    Bereich 0 bis 5 Sekunden. Null deaktiviert den DNS-Memo-Cache. Verwendet
    wird höchstens die kleinste verbleibende TTL der vollständigen Antwort
    und CNAME-Kette. Negative oder unvollständige Antworten werden nicht
    positiv zwischengespeichert.

.. confval:: resolver.cacheMaxHosts
    :type: integer
    :default: 32

    Bereich 1 bis 1024. Obergrenze positiver Hosteinträge je Resolverinstanz.
    Bei vollem Cache wird der älteste Eintrag entfernt.

.. confval:: resolver.maxAddresses
    :type: integer
    :default: 64

    Bereich 1 bis 64. Eine größere Antwort wird insgesamt abgelehnt und nicht
    auf die ersten erlaubten Kandidaten gekürzt.

.. confval:: resolver.maxCnameHops
    :type: integer
    :default: 8

    Bereich 0 bis 8. Zyklen und längere Ketten werden abgelehnt. Null verbietet
    das Folgen einer CNAME-Weiterleitung.

.. _configuration-redirect-tls:

Redirects und TLS
=================

.. confval:: redirects.max
    :type: integer
    :default: 5

    Bereich 0 bis 10. Obergrenze für die unterstützten Redirect-Pfade.
    Endpoint-Profile können Redirects zusätzlich ganz verbieten. PSR-18
    :php:`sendRequest()` folgt unabhängig davon keinem Redirect.

.. confval:: tls.requireVerification
    :type: boolean
    :default: false

    Bei :literal:`true` darf der aufrufende Client TLS-Verifikation nicht
    abschalten. Der Default :literal:`false` schaltet TLS-Verifikation nicht
    selbst aus: Der Transport verwendet normalerweise :literal:`verify=true`,
    lässt aber die ausdrücklich konfigurierte SDK-Option
    :literal:`verify=false` zu. :literal:`doctor` meldet den fehlenden
    Policy-Zwang als Warnung. Für interne HTTPS-Ziele ein korrektes CA-Bundle
    und :literal:`requireVerification=true` verwenden.

.. _configuration-logging:

Protokollierung
===============

.. confval:: logging.allowedSampleRate
    :type: integer|float
    :default: 0

    Endliche Zahl zwischen 0 und 1. Anteil protokollierter erlaubter
    Entscheidungen; null Prozent ist der Default. Ablehnungszähler bleiben
    auch bei gedrosselten Logereignissen erhalten.

.. confval:: logging.hostMode
    :type: string
    :default: 'hash'

    :literal:`hash` pseudonymisiert den Host mit dem konfigurierten HMAC-Key.
    Ohne Key wird der Host weggelassen. :literal:`plain` protokolliert den
    kanonischen Host im Klartext und ist eine bewusste Betreiberentscheidung.
    Pfad, Query und vollständige URL werden in beiden Modi nicht protokolliert.

.. confval:: logging.hostHmacKeyEnv
    :type: string|null
    :default: null

    Name einer echten Prozess-Umgebungsvariable, die den HMAC-Key enthält.
    Der Variablenname besteht aus ASCII-Buchstaben, Ziffern und Unterstrich,
    beginnt mit Buchstabe oder Unterstrich und hat höchstens 128 Zeichen.
    Der Key selbst gehört nicht in das Policyarray oder einen Request.

.. confval:: logging.denyRateLimitPerMinute
    :type: integer
    :default: 60

    Bereich 1 bis 10000. Drosselt Ablehnungsereignisse für Logs,
    nicht die Durchsetzung der Policy. Das Ereignisformat steht unter
    :ref:`operations-logging`.
