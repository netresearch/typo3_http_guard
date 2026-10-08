# Installation und Betrieb

Die Pakete sind noch nicht veröffentlicht. Für eine lokale Testinstanz werden beide Verzeichnisse als Composer-Path-Repositories eingebunden. Das folgende Beispiel setzt voraus, dass das TYPO3-Projekt neben dem entpackten Lieferverzeichnis liegt; die Pfade müssen zum Projekt passen.

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../http-guard-implementation/packages/http-guard",
      "options": {"versions": {"netresearch/http-guard": "dev-main"}}
    },
    {
      "type": "path",
      "url": "../http-guard-implementation/packages/nr-http-guard",
      "options": {"versions": {"netresearch/nr-http-guard": "dev-main"}}
    }
  ]
}
```

```sh
composer require netresearch/nr-http-guard:dev-main --with-all-dependencies
```

Composer darf dabei keine ungeprüfte TYPO3-Version oder gemischte Guzzle-Abhängigkeiten auswählen. Die Extension verlangt die beiden geprüften Core-Patches. Die Laufzeit akzeptiert genau diese Bibliothekskombinationen:

| Guzzle | Promises | PSR-7 |
|---|---|---|
| 7.15.5 | 2.5.3 | 2.13.1 |
| 8.2.0 | 3.0.2 | 3.1.0 |

Der kontrollierte Transport benötigt ext-curl, curl-multi und funktional mindestens libcurl 7.59.0. Diese Untergrenze sagt nichts über den Sicherheitsstand eines Betriebssystempakets aus. Der Betreiber prüft die aktuellen Herstellerhinweise und installiert Sicherheitsupdates. Die aufgezeichneten Builds und Quellen stehen im [Abhängigkeitsbericht](Abhaengigkeiten.md).

Echte Prozessvariablen für HTTP-/HTTPS-/ALL-/NO_PROXY werden konservativ abgelehnt, auch bei einem vermeintlichen NO_PROXY-Treffer. Es gibt keinen automatischen direkten Ersatz für einen vorgesehenen Unternehmensproxy. Proxybetrieb braucht einen gesondert geprüften Adapter. Ein eingehender HTTP-Header `Proxy` ist keine vertrauenswürdige Prozesskonfiguration; dafür enthält die Lieferung einen Test über eine echte PHP-SAPI.

## Policy und gebundene Clients

Ohne Konfiguration gilt `enforce` ohne interne Freigaben. Die Policy wird ausschließlich unter `EXTCONF.nr_http_guard` gesetzt. Beispielwerte sind vor dem Deployment durch die tatsächlichen Endpoints zu ersetzen:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard'] = [
    'schemaVersion' => 1,
    'mode' => 'enforce',
    'resolver' => ['staticHosts' => ['erp.internal.example' => ['10.23.4.12']]],
    'endpoints' => [
        'erp-orders' => [
            'origin' => 'https://erp.internal.example:8443',
            'allowedCidrs' => ['10.23.4.12/32'],
            'methods' => ['GET', 'POST'],
            'redirects' => 'none',
            'allowLoopback' => false,
            'purpose' => 'Bestellabgleich mit dem internen ERP',
            'owner' => 'ERP-Team',
        ],
    ],
];
```

Der ERP-Service erhält über `EndpointClientFactoryInterface::forEndpoint('erp-orders')` einen gebundenen PSR-18-Client. Die Profil-ID wird bei der Serviceverdrahtung festgelegt. Sie darf nicht aus einem Benutzerfeld stammen. Gewöhnliche RequestFactory-Aufrufe und Public-Fetch erhalten dadurch keinen Zugriff auf das ERP. Core-Kontextrestriktionen bleiben zusätzlich wirksam.

`PublicFetchClientInterface::fetch()` nimmt nur GET oder HEAD an und baut einen eigenen Request ohne geerbte Zugangsdaten, Cookies, Clientzertifikate oder Body auf. PSR-18 `sendRequest()` folgt keinen Redirects. Der generische Client unterstützt Body, Sink, geprüfte Callbacks, CA-Bundles und mTLS; rohe cURL-Optionen, verzögerter Versand im Transport, PHP-Streams und alternative Routen werden abgelehnt.

Policyobjekte bleiben für ihre Lebensdauer unverändert. Nach Änderungen sind TYPO3-System- und DI-Caches neu aufzubauen und langlebige Worker kontrolliert neu zu starten. Ein vorhandener Client behält seine alte Policy bis zum Austausch. Harte Ablaufzeiten werden vor jedem neuen Versuch erneut geprüft; bereits gestartete Übertragungen werden nicht als sofort widerrufen dargestellt.

## Einführung

1. RequestFactory, PSR-18, Vault, fremde SDKs, unabhängige Guzzle-Clients und direkte Socketaufrufe inventarisieren. Die Extension erfasst den dokumentierten Core-Pfad nach ihrer Registrierung. Frühe Bootstrap-Aufrufe und ein Ersatzhandler vor Middlewareeintritt benötigen eine eigene Integration.
2. In Staging `http-guard:config-check`, `http-guard:doctor` und `http-guard:legacy-report` ausführen. Diese Befehle senden kein Ziel-HTTP. `policy-check --no-dns` bleibt offline; ohne die Option darf der Befehl DNS verwenden, sendet aber ebenfalls kein HTTP.
3. Bei Bedarf ausdrücklich `observe` aktivieren. Dieser Modus ergänzt Diagnosen und blockiert keine zusätzlichen Requests. Vaults bisherige Prüfungen bleiben erhalten. Der Diagnosezustand lautet ungeschützt; beobachteter Traffic wird niemals automatisch freigegeben.
4. Interne Integrationen einzeln begründen, enge Netze und exakte Origin/Methoden festlegen und gebundene Clients verdrahten. Danach in einem kontrollierten Pilot auf `enforce` wechseln.
5. Vault getrennt migrieren. Der [Vault-Leitfaden](../integrations/nr-vault/README.md) beschreibt den Patch und separate Bindungen für Resource- und OAuth-Tokenendpoints. Die globale Extension behauptet keine automatische Vault-Abdeckung.

Eine falsch sortierte Middleware, fehlende Fähigkeit oder verbotene Option führt im geschützten Pfad zu einer ausdrücklichen Ablehnung. Der Modus wechselt bei Fehlern nicht automatisch.

## Betrieb und Rollback

Ablehnungen werden anhand des festen Reason-Codes, der Profil-ID und der Policyrevision untersucht. Logs enthalten keine Bodies, Headerwerte, Querys, Key-Pfade oder vollständigen Requests. Zähler behalten alle Entscheidungen, auch wenn Ereignislogs gedrosselt werden. DNS-Auflösung hat eigene Grenzen; Guzzle-Timeouts sind keine garantierte Gesamtdauer für Auflösung und HTTP.

Für einen bewussten Rollback wird die vorherige Policy samt Paket-/Dependency-Lock wiederhergestellt, anschließend werden Caches neu aufgebaut und Worker neu gestartet. Alternativ kann ausdrücklich `observe` oder `disabled` deployt werden. Beide Modi bedeuten eine reduzierte Sicherheitslage; bestehende Vault-Kontrollen werden dabei nicht entfernt. Nach dem Deployment muss `doctor` den gewählten ungeschützten Modus mit Exitcode 2 melden. Funktions- und Auditprüfungen werden wiederholt. Eine hohe Ablehnungsrate löst keinen automatischen Rollback aus.

Die Übergänge nach `observe` und `disabled` sind in den echten Core- und Vault-Testinstanzen ausgeführt. Ein Rollback im späteren Betreiberprojekt muss dessen eigene Deployment-, Cache- und Workerabläufe prüfen.

## Freigabe

Vor einem Produktionsdeployment sind der [Prüfbericht](Pruefbericht.md), die Anforderungszuordnung und die Auditbefunde zu prüfen. Die im Plan verlangte unabhängige menschliche Sicherheitsprüfung ist durch Agentenreviews nicht ersetzt. Für den Betreiberpilot fehlen bislang eine benannte Testinstanz und die konkreten freizugebenden Endpoints.
