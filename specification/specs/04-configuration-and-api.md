# 04 - Konfiguration und API-Verträge

Stand: 2026-10-08. Status: vorgeschlagen. **Sämtliche nachfolgenden `HttpGuard`-Klassen, Optionen und CLI-Kommandos sind neu zu implementierende APIs, keine heute vorhandenen TYPO3-Funktionen.**

## 1. Quelle und Schema

TYPO3-Adapter liest ausschließlich deployte Konfiguration unter:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']
```

Keine Freigaben aus TypoScript, Site-Requestparametern, Backend-Formularen oder entfernten Antworten in v1. Ein Read-only-Statusmodul ist später möglich; ein Policyeditor ist kein Lieferumfang. Unbekannte Schlüssel und falsche Typen sind Konfigurationsfehler, nicht ignorierte Werte.

| Schlüssel | Typ / Default | Semantik |
|---|---|---|
| `schemaVersion` | Integer, `1` | Andere Werte werden abgelehnt |
| `mode` | `enforce`, `observe`, `disabled`; `enforce` | Expliziter Betriebsmodus |
| `deniedCidrs` | Liste kanonischer CIDRs, `[]` | Zusätzliche absolute Verbote |
| `endpoints` | Map Profil-ID -> Endpoint, `[]` | Nur explizit gebundene Freigaben |
| `resolver.staticHosts` | Map Host -> nichtleere IP-Liste, `[]` | Kontrollierte Adressquelle, keine Freigabe |
| `resolver.cacheTtlSeconds` | Integer 0..5, `5` | Obergrenze für positive Memoisierung |
| `resolver.cacheMaxHosts` | Integer 1..1024, `32` | FIFO/LRU-Begrenzung; keine unbeschränkte Map |
| `resolver.maxAddresses` | Integer 1..64, `64` | Überlauf wird abgelehnt, nicht still abgeschnitten |
| `resolver.maxCnameHops` | Integer 0..8, `8` | Zyklen immer ablehnen |
| `redirects.max` | Integer 0..10, `5` | Globale maximale Hopzahl |
| `tls.requireVerification` | Boolean, `false` | Bei true zusätzlich `verify=false` verbieten; sonst warnen |
| `logging.allowedSampleRate` | Zahl 0..1, `0` | Erlaubte Verbindungen standardmäßig nicht einzeln loggen |
| `logging.hostMode` | `hash` oder `plain`; `hash` | Hash nur mit konfiguriertem HMAC-Schlüssel |
| `logging.hostHmacKeyEnv` | Env-Variablenname oder null | Bei fehlendem Key Host auslassen; nicht schwach hashen |
| `logging.denyRateLimitPerMinute` | Integer 1..10000, `60` | Ereignisrate begrenzen, Zähler behalten |

### Endpoint-Schema

| Feld | Typ | Bedeutung |
|---|---|---|
| `origin` | Absolute Origin ohne Pfad/Query/Userinfo | Scheme, exakter Host, effektiver Port |
| `allowedCidrs` | Nichtleere Liste | Alle verwendbaren Ziel-IPs müssen darin liegen |
| `methods` | Nichtleere Liste HTTP-Methoden | Nur diese Methoden; CONNECT ist in v1 generell verboten |
| `redirects` | `none` oder `same-origin`; `none` | Keine Origin-Erweiterung |
| `allowLoopback` | Boolean; `false` | Nur zusammen mit /32 bzw. /128 erlaubt |
| `purpose` | Nichtleerer Text, maximal 200 Zeichen | Fachlicher Grund; keine Secrets |
| `owner` | Nichtleerer Text, maximal 120 Zeichen | Verantwortliche Rolle/Gruppe, kein frei erfundener Name |
| `reviewAfter` | Optionales Datum YYYY-MM-DD | Nach Ablauf Diagnosewarnung, kein automatischer Ausfall |
| `expiresAt` | Optionaler RFC3339-Zeitpunkt | Harte Ablaufgrenze; ab dann keine Freigabe |

Wildcards, `/0`, komplette RFC1918-Freigaben ohne engere Begrenzung und eine `allowPrivate=true`-Generalausnahme sind nicht Teil des Schemas. Als weite Freigaben gelten IPv4-CIDRs breiter als /24 und IPv6-CIDRs breiter als /64; v1 lehnt sie im Endpoint-Schema ab. Bedarf für größere Netze verlangt eine bewusst geänderte Policy, nicht eine unbemerkte Lockerung. Überlappende CIDRs desselben Profils werden normalisiert; konkurrierende Profile werden niemals automatisch anhand einer URL ausgewählt.

Pfad-Allowlisting ist bewusst nicht Teil des v1-Netzwerkguards. Pfadprüfungen unterscheiden sich durch URL-Encoding, Proxy-/Servernormalisierung und Routing; fachliche Autorisierung bleibt in der jeweiligen Integration. Ein späterer Pfadfilter braucht eine eigene Normalisierungsspezifikation und darf kein Ersatz für Zugriffskontrolle sein.

## 2. Konfigurationsbeispiel

Vorgeschlagenes Schema, kein Drop-in-Code für eine existierende Extension:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard'] = [
    'schemaVersion' => 1,
    'mode' => 'enforce',
    'deniedCidrs' => [],
    'resolver' => [
        'staticHosts' => [
            'erp.internal.example' => ['10.23.4.12'],
            'llm.internal.example' => ['10.23.4.30'],
        ],
        'cacheTtlSeconds' => 5,
        'cacheMaxHosts' => 32,
        'maxAddresses' => 64,
        'maxCnameHops' => 8,
    ],
    'endpoints' => [
        'erp-orders' => [
            'origin' => 'https://erp.internal.example:8443',
            'allowedCidrs' => ['10.23.4.12/32'],
            'methods' => ['GET', 'POST'],
            'redirects' => 'none',
            'allowLoopback' => false,
            'purpose' => 'Order synchronization with the internal ERP',
            'owner' => 'ERP integration maintainers',
        ],
        'local-llm' => [
            'origin' => 'https://llm.internal.example',
            'allowedCidrs' => ['10.23.4.30/32'],
            'methods' => ['POST'],
            'redirects' => 'none',
            'allowLoopback' => false,
            'purpose' => 'Internal model inference',
            'owner' => 'AI platform maintainers',
        ],
    ],
    'redirects' => ['max' => 5],
];
```

Eine normale URL-Importfunktion bekommt **keinen** EndpointGrant und darf trotz dieser Konfiguration weder ERP noch LLM erreichen. Statische Hosts alleine ändern daran nichts.

## 3. Öffentliche API der Bibliothek

Die folgenden Signaturen sind Verträge; DTO-Felder und Fehlersemantik werden bei Implementierung in PHP-Typen überführt.

```php
interface OutboundPolicyEvaluatorInterface
{
    public function evaluate(
        RequestInterface $request,
        RequestPolicyContext $context,
    ): PolicyDecision;
}

interface EndpointClientFactoryInterface
{
    public function forEndpoint(string $configuredEndpointId): ClientInterface;
}

interface PublicFetchClientInterface
{
    public function fetch(UriInterface $uri, string $method = 'GET'): ResponseInterface;
}
```

`evaluate()` liefert eine Diagnoseentscheidung, keinen nachträglich für beliebigen Versand nutzbaren Token. Nur der interne Terminalpfad kann einen ConnectionPlan ausstellen und unmittelbar konsumieren. `forEndpoint()` ist für vertrauenswürdige Serviceverdrahtung vorgesehen; der Profilname darf nicht aus einem vom Nutzer auswählbaren Requestfeld stammen. Für gewöhnliche Anwendungsklassen soll ein bereits gebundener PSR-18-Client injiziert werden.

`RequestPolicyContext` enthält Modus, Policyrevision und optional einen Registry-eigenen EndpointGrant. Ein Grant ist ein nicht serialisierbares Objekt, an Registrygeneration und konkretes Profil gebunden. Er ist eine Schutzmaßnahme gegen versehentliche Verwechslung, keine Sicherheitsgrenze gegen bösartigen PHP-Code. Die interne Guzzle-Option heißt vorgeschlagen `nr_http_guard_grant`; beliebige Strings, Arrays oder Objekte falscher Herkunft werden abgelehnt.

Die gemeinsame Bibliothek stellt Integrationsadapter für Guzzle bereit. Vaults credentialtragende öffentliche API gibt weder einen rohen Guzzleclient noch die Transferlease aus. Ein interner Vault-Service darf die Transferfactory bewusst verwenden.

## 4. Fehlervertrag

Guard-Fehler implementieren eine gemeinsame `OutboundPolicyExceptionInterface` und sind in PSR-18 als `ClientExceptionInterface` erkennbar. Ein nicht sendbarer Request kann zusätzlich `RequestExceptionInterface` implementieren; der Zugriff auf den enthaltenen Request ist ausdrücklich sensitiv. Guzzle-Aufrufe erhalten eine kompatible Runtime-Exception/abgelehnte Promise. Keine fachliche Ablehnung wird als ConnectionException getarnt.

| Reason-Code | Ursache | Retry |
|---|---|---|
| `invalid_target` | Mehrdeutige/ungültige URL, Host oder Port | Nein |
| `scheme_forbidden` | Nicht HTTP/HTTPS | Nein |
| `authority_mismatch` | Host-Header/URI oder Originwechsel in Middleware | Nein |
| `address_forbidden` | Mindestens eine Adresse gesperrt | Nein |
| `resolution_unverified` | Keine brauchbare Adresse | Nur neuer bewusster Aufruf, kein automatischer Guard-Retry |
| `resolution_limit` | Zu viele Adressen/Aliase oder Zyklus | Nein |
| `endpoint_mismatch` | Origin, Methode oder IP-Menge außerhalb Profil | Nein |
| `grant_invalid` | Unbekannter, ungültiger oder abgelaufener Grant | Nein |
| `transport_unsupported` | Kein abgesicherter Transport | Nein |
| `proxy_unsupported` | Expliziter/impliziter Proxy | Nein |
| `option_forbidden` | Transportmanipulierende Option | Nein |
| `redirect_forbidden` | Cross-Origin, Downgrade oder Hoplimit | Nein |
| `configuration_invalid` | Schema/Reihenfolge/Capability ungültig | Nein |

Fehlermeldungen enthalten einen festen Text und den Reason-Code, nicht die volle URL. Technische Ausnahmen werden nicht ungefiltert geloggt. Ein Debugobjekt mit Rohrequest darf nicht Bestandteil normaler Telemetrie sein. Ein fehlgeschlagener HTTP-Status des erlaubten Zielservers bleibt ein normaler HTTP-Status; der Guard erfindet dafür keine Policyablehnung.

## 5. Logging und Metriken

Ein Entscheidungsereignis enthält: Ereignisversion, Zeitpunkt, `mode`, `decision` (`allow`, `deny`, `would_deny`, `unverifiable`), Reason-Code, Profil-ID, Policyrevision, Adressklasse, Scheme, Port, Resolverquelle und Korrelations-ID. Host nur nach obiger Hash-/Plainregel. Kein Body, keine Headerwerte, kein Query, kein Pfad, keine Cookies, keine Client-Key-Pfade, kein kompletter Exceptiondump.

Metriken haben nur niedrigkardinale Labels: Modus, Entscheidung, Reason-Code, statisch konfigurierte Profil-ID. Hostnamen und einzelne IPs sind keine Metriklabels. Ablehnungen werden gezählt, auch wenn Logereignisse gedrosselt werden. Loggingfehler dürfen eine Ablehnung nicht in eine Freigabe verwandeln; standardmäßig ist Logausfall aber kein zusätzlicher Grund, eine sonst zulässige HTTP-Anfrage zu blockieren. Vaults eigene Auditregeln bleiben separat.

## 6. Vorgeschlagene CLI

```text
vendor/bin/typo3 http-guard:doctor
vendor/bin/typo3 http-guard:policy-check <url> [--endpoint=<id>]
vendor/bin/typo3 http-guard:config-check
vendor/bin/typo3 http-guard:legacy-report
```

`doctor`: keine Zielverbindungen; zeigt erfasste Integration, Modus, Registryreihenfolge, PHP/Guzzle/cURL, Proxyherkunft nur ohne Geheimwerte, Konfigurationsrevision, bekannte Bypässe und Einschränkungen. `policy-check` kann DNS auslösen, sendet aber niemals HTTP und nimmt keine Requests mit Credentials an. `config-check` arbeitet offline. `legacy-report` zeigt migrierbare Konfigurationsstellen, ändert sie nicht.

Exitcodes: 0 = erfolgreich/zulässig; 2 = Policyablehnung bzw. nicht geschützter Modus bei `doctor`; 3 = Konfigurations-/Capabilityfehler; 4 = nicht verifizierbare Auflösung. Kein Kommando prüft automatisch reale Metadaten- oder Adminendpunkte. Kein automatisches "allow suggested endpoint".

## 7. Kompatible Optionen im globalen Adapter

Requestmethoden außer CONNECT bleiben grundsätzlich zulässig, sofern sie gültige HTTP-Token und im Endpointprofil erlaubt sind. Öffentliche Ports sind standardmäßig 1..65535, weil die Aufgabe Netzwerkschutz und kein universelles Port-Allowlisting ist; Endpoint-Ausnahmen binden dagegen immer einen exakten Port.

`timeout`, `connect_timeout`, `verify`, `cert`, `ssl_key`, `sink`, `decode_content` und übliche requestbezogene HTTP-Optionen werden typgeprüft erhalten. `debug` ist im geschützten Pfad aus, um neue Secret-Logs zu verhindern. Custom-Callbacks wie `on_headers`, `progress`, `on_stats` müssen mit den internen Callbacks komponiert statt überschrieben werden; sie erhalten keine Freigabe, interne Securityoptionen zu ändern. Die freigegebene Optionsliste wird je Guzzle-Major getestet. Nur intern vom Guzzle-Standardstack erzeugte, dokumentierte Optionen dürfen zusätzlich passieren; es gibt kein allgemeines Durchreichen unbekannter Optionen.
### 7.1 Normative Optionenklassen

Die Tabelle beschreibt den Vertrag am kontrollierten Pfad. Die konkrete Guzzle-Repräsentation wird im Majoradapter umgesetzt, nicht durch beliebiges `array_merge`.

| Option / Gruppe | Regel in Enforce v1 |
|---|---|
| `timeout`, `connect_timeout` | Endliche nichtnegative Zahl; bestehende Nullsemantik bleibt erhalten; kein vorgetäuschter DNS-Abbruch |
| `verify` | Bool oder lesbarer CA-Bundlepfad; `false` nur wenn Betreiberpolicy es zulässt, mit Diagnose |
| `cert`, `ssl_key` | Dokumentierter Guzzle-Pfad bzw. Pfad/Passphrase-Paar; kein Passphrase-/Pfadlogging |
| `version` | Getestete HTTP-Version 1.0, 1.1 oder 2.0; keine ungeprüfte HTTP/3-/Alt-Svc-Aktivierung |
| `headers`, `body`, `json`, `form_params`, `multipart`, `query` | Normale Guzzle-Aufbereitung bleibt zuständig; Guard validiert das tatsächlich erzeugte PSR-7-Requestziel, nicht nur Rohoptionen |
| `auth`, `cookies` | Im generischen Client bestehende Fachsemantik; Same-Origin bleibt Pflicht. Im Public-Fetch-Client nicht erlaubt |
| `allow_redirects` | Bool oder bekannter Guzzle-Redirectoptionssatz; aktiver Maximalwert muss innerhalb Policy liegen. Protokolle nur HTTP/HTTPS, kein Downgrade. `false` bleibt aus |
| `http_errors` | Bestehende Middlewaresemantik bleibt erhalten; Policyfehler sind davon unabhängig |
| `sink` | Guzzle-kompatibler Stream/Pfad; kein zusätzliches Guard-Bodybuffering. Fachcode verantwortet lokale Pfadautorisierung |
| `decode_content`, `expect` | Dokumentierte, typkorrekte Guzzle-Semantik; Größenlimits des Fachclients bleiben separat |
| `on_headers`, `on_stats`, `progress`, `on_redirect` | Callback validieren und mit internen Beobachtern komponieren; Exceptions dürfen Ressourcenfreigabe nicht verhindern. Rückgabewert hebt Policy nicht auf |
| `force_ip_resolve` | Nur `v4` oder `v6`; alle empfangenen Kandidaten trotzdem vor Familienwahl klassifizieren; kein Wegfiltern verbotener Antworten |
| `idn_conversion` | Nur deaktiviert; Unicode muss vor dem Guard kontrolliert in ASCII umgewandelt worden sein |
| `debug` | Aus; Aktivierung mit sensitivem HTTPverkehr ist im geschützten Pfad nicht vorgesehen |
| `delay` | Nur abwesend oder numerisch null. Verzögerungen werden vor Aufruf des Guards geplant, nicht nach Planausstellung |
| `stream`, `read_timeout` | `stream=true` und nicht anwendbare PHP-Streamoptionen ablehnen; kein StreamHandler |
| `proxy`, geerbte Proxyumgebung | Kein Proxybetrieb in v1; kein stiller Direct-Fallback |
| `curl`, `curl_multi`, `stream_context`, `transport_sharing`, fremde Transportfactories | Von extern nicht zugelassen; Securityoptionen ausschließlich aus internem Plan und Majoradapter |
| `handler` | Ein Ersatz vor Middlewareeintritt liegt außerhalb Abdeckung; ein im kontrollierten Pfad eingeschleuster Ersatz wird abgelehnt |
| Guard-eigenes Envelope/Grant | Registry-/Scope-eigene Objekte, nicht serialisierbar; keine frei gelieferten Nachbildungen |
| Guzzle-interne Optionen | Exakt inventarisierte Major-Allowlist; Typen und Grenzen prüfen. Kein allgemeines `_`-Präfix als Freifahrtschein |
| Unbekannte Optionen | `option_forbidden`, bis ein kompatibler Adapter sie ausdrücklich klassifiziert |

Ein Securitycallback darf nicht vom Aufrufer ersetzt werden. Die Reihenfolge, Argumente und Fehlerbehandlung zusammengesetzter Callbacks gehören zu T041/T061/T083. Timeout- und Sinkkompatibilität bedeutet nicht, dass beliebiger externer Code in einem Callback als untrusted PHP isoliert wäre.

Ein verzögerter Retry erhält erst nach der Wartezeit einen neuen Plan. T038/T052/T041 prüfen nonzero `delay`, Grantablauf und erneute Autorisierung. Es gibt kein Wiederverwenden eines zuvor erstellten Plans nach beliebig langer Queuezeit.

### 7.2 Proxyherkunft

Nur die tatsächlich von der Laufzeit/Guzzle verwendete Konfiguration ist maßgeblich. Prozessumgebung, Guzzledefaults und explizite Requestoptionen werden unterschieden; Variablennamen und NO_PROXY-Semantik werden in der Ziel-SAPI getestet. Ein durch einen eingehenden Header entstandenes `$_SERVER['HTTP_PROXY']` darf nicht ungeprüft wie vertrauenswürdig gesetzte Prozessumgebung behandelt werden.

Konservativ verweigert v1 auch mehrdeutige echte Proxykonfiguration statt einen vermeintlichen NO_PROXY-Treffer zu raten. Nach erfolgreicher Konfigurationsprüfung erzeugt der kontrollierte Transport einen ausdrücklich direkten, nicht erbenden Pfad. Das ist kein Bypass einer erkannten Unternehmensproxyvorgabe: eine solche Vorgabe wird zuvor abgelehnt.

### 7.3 Public-Fetch und Core-Allowlist

`fetch()` akzeptiert nur GET oder HEAD. Sein Requestaufbau verwirft globale Auth-, Cookie- und mTLS-Defaults und nimmt keine callerdefinierten Bodies/Header an. Er benutzt einen eigenen bekannten, nicht sensitiven Header-Allowset. Er folgt mittels des kontrollierten Guzzle-Requestpfads; dies ändert nicht die No-Follow-Semantik eines PSR-18-`sendRequest()`.

Bei Verwendung im TYPO3-Adapter gelten konfigurierte Core-Kontextrestriktionen weiterhin. Der explizite Wrapper muss seinen fest verdrahteten Core-Kontext in die Clientkonstruktion einbringen; ein Benutzer kann ihn nicht als Freigabe auswählen. Der globale Middlewarepfad erfindet keinen automatisch verfügbaren RequestFactory-Kontext. Die frameworkfreie Bibliothek erhält entsprechende Einschränkungen als injizierte Policy, nicht durch Zugriff auf Globals.
