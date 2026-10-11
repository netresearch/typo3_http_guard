.. _api:

=================
Clients verwenden
=================

Die öffentlichen Interfaces stehen unter :php:`Netresearch\HttpGuard` und
werden durch TYPO3-Services bereitgestellt. Anwendungen sollen gebundene
PSR-18-Clients oder Public Fetch injizieren. Der interne Guzzle-Client und
sein Fortschritts-/Abbruchtreiber gehören zusammen und bilden keine
zusätzliche öffentliche Credential-API.

.. _api-core:

Gewöhnliche Core-Anfragen
========================

Der registrierte Core-Pfad erhält die öffentliche Standardpolicy. Bereits
vorhandene unterstützte Core-Middleware und Kontextrestriktionen bleiben
zusätzlich wirksam. Ein gewöhnlicher Aufruf kann beispielsweise so aussehen:

.. code-block:: php
    :caption: Öffentlicher Abruf mit TYPO3 RequestFactory

    $response = $requestFactory->request(
        'https://www.example.org/document.json',
        'GET',
        ['timeout' => 10, 'http_errors' => false],
    );

:php:`$requestFactory` ist ein injizierter
:php:`TYPO3\CMS\Core\Http\RequestFactory`. Für öffentliche Inhalte aus
unvertrauenswürdigen URLs ist :ref:`api-public-fetch` die engere API.
RequestFactory-Anfragen übernehmen durch ein konfiguriertes Endpoint-Profil
keine internen Rechte.

.. _api-endpoint:

Gebundene Integrationsclients
=============================

.. php:class:: Netresearch\HttpGuard\EndpointClientFactoryInterface

    .. php:method:: forEndpoint(string $configuredEndpointId)
        :returntype: Psr\Http\Client\ClientInterface

        Erzeugt einen Client mit einem konfigurierten Endpoint-Profil.
        Unbekannte, fremde oder abgelaufene Bindungen werden abgelehnt.

Die Profil-ID steht im vertrauenswürdigen Servicecode oder dessen
Dependency-Injection-Konfiguration. Sie wird nicht aus der jeweiligen
Benutzeranfrage übernommen. Das folgende Beispiel verwendet das Profil
aus :ref:`configuration-example`:

.. literalinclude:: _ErpClient.php
    :language: php
    :caption: Classes/Service/ErpClient.php im Sitepackage

Der Client prüft Origin, Methode und alle aufgelösten Adressen bei jedem
Versand. PSR-18 :php:`sendRequest()` folgt keinem Redirect und gibt auch
HTTP-Fehlerantworten wie 404 oder 500 als Response zurück. Netzwerkfehler
und Policyablehnungen bleiben Exceptions. Ein Policyfehler darf nicht durch
einen ungeschützten Ersatzclient umgangen werden.

.. _api-public-fetch:

Public Fetch
============

.. php:class:: Netresearch\HttpGuard\PublicFetchClientInterface

    .. php:method:: fetch(Psr\Http\Message\UriInterface $uri, string $method = 'GET')
        :returntype: Psr\Http\Message\ResponseInterface

        Akzeptiert ausschließlich GET oder HEAD und baut einen eigenen
        öffentlichen Request ohne geerbte Zugangsdaten oder Body auf.

Public Fetch übernimmt keine Authentifizierung, Cookies, Clientzertifikate,
SSL-Keys, benutzerdefinierte Headers, Bodies oder Query-Defaults eines
anderen Clients. Seine feste Headerliste besteht aus :literal:`Accept`,
:literal:`Accept-Encoding` und :literal:`User-Agent`. Die Query einer
zulässigen Ziel-URI bleibt Bestandteil dieser URI; sie wird nicht geloggt.
Erlaubte öffentliche Redirects können eine andere Origin erreichen, ohne
dass Geheimnisse eines ursprünglichen Integrationsrequests mitwandern.

.. literalinclude:: _PublicDocument.php
    :language: php
    :caption: Roh-URL vor Erstellung eines PSR-7-Objekts prüfen

Für einen Benutzerabruf wird keine Endpoint-ID akzeptiert. Private Ziele
bleiben auch dann gesperrt, wenn das Projekt ein gleichnamiges internes
Endpoint-Profil besitzt.

.. _api-raw-uri:

Roh-URLs und PSR-7
=================

:php:`TargetNormalizer::assertRawUri(string $uri)` prüft verbotene Syntax,
solange die unveränderte Zeichenkette vorhanden ist. Fragmente einschließlich
eines leeren :literal:`#`, Backslashes, Steuerzeichen und ungültige rohe
Schreibweisen sind vor der URI-Erstellung abzulehnen.

Ein PSR-7-URI-Objekt kann ein leeres Fragmentkennzeichen bereits verworfen
oder Eingaben kodiert haben. Ein späterer PSR-18-Client kann diese Information
nicht rekonstruieren. Deshalb prüft der TYPO3-RequestFactory-Adapter rohe
Strings vor Guzzle. Eigene PSR-18-Anwendungen prüfen die ursprüngliche URL
selbst, wie im Public-Fetch-Beispiel. Diese Grenze gilt auch für rohe
Vault-Resource-URLs vor Erstellung eines RequestInterface-Objekts.

.. _api-errors:

Policyfehler behandeln
=====================

:php:`PolicyException` implementiert
:php:`OutboundPolicyExceptionInterface`. Die Methode :php:`reasonCode()`
liefert einen stabilen Code aus :ref:`operations-reasons`. Exceptiontexte
enthalten keine URL, Zugangsdaten oder Request-Bodies. Anwendungen können
den Code für eine verständliche Fehlermeldung verwenden; Geheimnisse oder
vollständige Requests gehören nicht in einen ergänzenden Fehlerlog.

.. _api-sdk-options:

SDK-Optionen und Streaming
=========================

Im kontrollierten RequestFactory-/SDK-Pfad bleiben reguläre Request-Bodies,
Headers und unterstützte Authentifizierung wirksam. :literal:`timeout` und
:literal:`connect_timeout` akzeptieren endliche nicht negative Zahlen,
einschließlich null. :literal:`verify` akzeptiert Boolean oder ein lesbares
CA-Bundle, unter Beachtung der TLS-Policy. Lesbare Clientzertifikate und
SSL-Keys ermöglichen mTLS. :literal:`sink` kann in einen Dateipfad, eine
Resource oder einen PSR-7-Stream schreiben, ohne eine zweite eigene
Responsekopie im Guard anzulegen.

Unterstützte Callbacks wie :literal:`on_headers`, :literal:`on_stats` und
:literal:`progress` bleiben erhalten; ihre Exceptions beenden den
zugehörigen Transfer. HTTP 1.0, 1.1 und 2 sind qualifiziert, HTTP/3 nicht.
Die entsprechende IP-Familienoption kann ausschließlich :literal:`v4` oder
:literal:`v6` wählen und erweitert keine Adressfreigabe.

Rohe :literal:`curl`-/cURL-multi-Optionen, :literal:`stream_context`,
Unix-Sockets, Proxy-Routen, eigene Handler, Transport-Sharing,
verzögerter Transportversand, Debug-Dumps, unbekannte Optionen und
:literal:`stream=true` werden im geschützten Pfad abgelehnt. Eine
Response-Sink-Datei ist kein alternativer Stream-HTTP-Handler.
Guzzle-intern erzeugte und quellgeprüfte Digest-/NTLM-Steuerung unter
Guzzle 7 ist von beliebigen rohen cURL-Optionen unterschieden.

Abbruch und inkrementelles Streaming benötigen einen Adapter, der den
zugehörigen internen Fortschrittstreiber verwendet. Der aufgezeichnete
nr-vault-Referenzadapter tut dies für seine bestehenden APIs; ein beliebiger zurückgegebener
Guzzle-Client allein garantiert diese Lebensdauerbindung nicht.

.. _api-vault:

Optionale nr-vault-Migration
===========================

Die Extension liest keine Vault-Secrets und übernimmt Vault nicht
automatisch. Der historische Referenzpatch ist für seinen aufgezeichneten
nr-vault-Quellstand vorgesehen und benötigt eine bewusste Projektintegration.
Resource- und OAuth-Token-Origin benötigen eigene gebundene Clients und
gegebenenfalls eigene Profile. Eine private Token-Origin erbt weder eine
öffentliche Resource-Freigabe noch umgekehrt.

Secret-Abruf, Audit, Maskierung, Größenlimits und Vaults bestehende
Cancel-/Streaming-Semantik bleiben Aufgaben des Vault-Adapters. Dessen
konkrete `Referenzmigration samt Nachweisen
<https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/integrations/nr-vault/EVIDENCE.md>`_
bleibt an diesen historischen Stand gebunden. Die Extension liefert kein
Vault-Overlay und übernimmt weder Deployment noch eine neue vollständige
Vault-Prüfung. Der reguläre TYPO3-RequestFactory-Schutz benötigt keine
Vault-Migration.
