.. _configuration-endpoints:

================
Endpoint-Profile
================

Ein Profil bindet eine begründete Integration an Origin, Methoden und enge
Netze. Der Client erhält seine Bindung durch
:php:`EndpointClientFactoryInterface::forEndpoint()` beim Aufbau des
vertrauenswürdigen Services. Die Profil-ID darf nicht aus Benutzerfeldern,
URL-Parametern oder Request-Headern abgeleitet werden.

.. _configuration-endpoint-fields:

Felder
======

.. confval:: origin
    :type: string
    :default: kein Default; erforderlich

    Exakte HTTP- oder HTTPS-Origin aus Scheme, Host und optionalem Port.
    Kein Pfad, auch kein abschließender Slash, keine Query, kein Fragment
    und keine Zugangsdaten. Beispielsweise
    :literal:`https://erp.internal.example:8443`. IPv6-Literale benötigen
    eckige Klammern. Standardports werden kanonisiert.

.. confval:: allowedCidrs
    :type: list<string>
    :default: kein Default; erforderlich

    Nicht leere Liste. IPv4-Netze dürfen nicht breiter als /24 sein,
    IPv6-Netze nicht breiter als /64. Eine einzelne Adresse wird als /32
    beziehungsweise /128 angegeben. Alle gefundenen Adressen müssen zu
    diesen Netzen passen, auch bei einem öffentlich auflösenden Endpoint.
    Betreiber-Sperren und harte Metadaten-Sperren bleiben vorrangig.

.. confval:: methods
    :type: list<string>
    :default: kein Default; erforderlich

    Nicht leere Liste gültiger HTTP-Methoden-Tokens; Vergleich exakt und
    case-sensitiv. Die üblichen Namen wie :literal:`GET` und :literal:`POST`
    werden großgeschrieben. :literal:`CONNECT` ist stets ausgeschlossen.

.. confval:: redirects
    :type: string
    :default: 'none'

    :literal:`none` verbietet Redirects. :literal:`same-origin` gestattet sie
    innerhalb derselben Origin unter der allgemeinen Redirect-Obergrenze.
    Ein Redirect erteilt keine Berechtigung für einen anderen Endpoint.

.. confval:: allowLoopback
    :type: boolean
    :default: false

    Loopback benötigt gleichzeitig :literal:`true` und genau eine freigegebene
    /32-IPv4- beziehungsweise /128-IPv6-Adresse. Ein gesamtes
    :literal:`127.0.0.0/8` bleibt ungültig. Der Schalter erlaubt keine anderen
    besonderen oder Metadatenadressen.

.. confval:: purpose
    :type: string
    :default: kein Default; erforderlich

    Nicht leerer UTF-8-Zweck mit höchstens 200 Zeichen und ohne Steuerzeichen.
    Beschreibt die konkrete Verbindung, beispielsweise den ERP-Bestellabgleich.

.. confval:: owner
    :type: string
    :default: kein Default; erforderlich

    Nicht leere verantwortliche Stelle, höchstens 120 UTF-8-Zeichen und ohne
    Steuerzeichen. Keine Zugangsdaten oder personenbezogenen Geheimnisse.

.. confval:: reviewAfter
    :type: string|null
    :default: null

    Echtes Kalenderdatum im Format :literal:`YYYY-MM-DD`. Nach Ablauf meldet
    :literal:`config-check` und :literal:`doctor` eine überfällige Prüfung;
    das Profil wird dadurch nicht automatisch deaktiviert.

.. confval:: expiresAt
    :type: string|null
    :default: null

    Echter Zeitpunkt mit Zeitzone, etwa :literal:`2027-04-01T00:00:00Z`.
    Optionale Sekundenbruchteile haben höchstens sechs Stellen. Kein Datum
    ohne Uhrzeit, keine implizite Serverzeitzone. Nach Ablauf wird jeder neue
    Versuch abgelehnt, einschließlich Redirect, Retry und Versand nach
    Secret-/Body-Vorbereitung. Bereits gestartete Übertragungen werden nicht
    als sofort widerrufen dargestellt.

.. _configuration-endpoint-networks:

Adressklassen und Widerruf
=========================

Eine gebundene Freigabe kann enge RFC1918-, ULA- und CGNAT-Netze erlauben.
Loopback hat die zusätzliche Einzeladressregel. Link-Local, Multicast,
Dokumentationsnetze, besondere reservierte Bereiche und bekannte
Metadatenadressen erhalten dadurch keine allgemeine Ausnahme.

IPv4-mapped IPv6-Ziele werden als eingebettete IPv4-Adresse klassifiziert und
mit IPv4-Netzen verglichen. Eine Konfiguration wie
:literal:`::ffff:10.23.4.12/128` ist ungültig; stattdessen
:literal:`10.23.4.12/32` verwenden.

Profiländerungen erfordern den Austausch des Konfigurations-Snapshots und
der daraus erzeugten Clients. Eine andere Registry lehnt fremde oder alte
Kontexte ab. Ein im alten Worker verbleibender Client besitzt weiterhin
seine alte Registry: Für einen operativen Widerruf muss dieser Worker neu
gestartet werden. Ein DNS-Cache-Treffer umgeht weder aktuelle Profilprüfungen
der verwendeten Registry noch :literal:`expiresAt`.
