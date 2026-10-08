# 03 - Architektur und Laufzeitverhalten

Stand: 2026-10-08. Status: vorgeschlagen. Insbesondere die Integration aus Abschnitt 3 ist vor Umsetzung durch AP-01 zu beweisen.

## 1. Komponenten und Abhängigkeiten

Arbeitspakete/Composer-Namen:

```text
netresearch/http-guard                    (gemeinsame PHP-Bibliothek)
     ^                         ^
     |                         |
netresearch/nr-http-guard     netresearch/nr-vault
(TYPO3-Integration)           (Vault-Adapter)
```

Diese Namen sind Vorschläge. Die gemeinsame Bibliothek darf keine Vault-Datenbank, keine TYPO3-Konfiguration und keine globalen Variablen lesen. Sie bekommt Policies, Resolver, Clock und Logger injiziert. Ein Integrationspaket für ein anderes Framework ist damit möglich, aber kein v1-Liefergegenstand.

| Komponente | Verantwortung |
|---|---|
| `TargetNormalizer` | Aus PSR-7-Request ein kanonisches `Target` bilden |
| `AddressClassifier` | Binäre IP-/CIDR-Prüfung und versionierter Spezialbereichskorpus |
| `ResolverInterface` | Exakte Hostnamen in validierbare Adresskandidaten auflösen |
| `StaticThenDnsResolver` | Statische Hosteinträge, sonst DNS; niemals ungeprüfter Transportfallback |
| `PolicyRegistry` | Validierte, unveränderliche Profile und deren Revision |
| `PolicyEngine` | Ziel, Methode, Grant und Adressmenge zu Allow/Deny auswerten |
| `ConnectionPlan` | Interne, unveränderliche Bindung von Ziel, IP-Menge und Policy |
| `BoundaryMiddleware` | Anfangsorigin erfassen, Reihenfolge prüfen, finale Redirect-Antwort prüfen |
| `TerminalGuardMiddleware` | Endgültiges Ziel und Optionen prüfen; kontrollierten Transport starten |
| `GuardedTransferFactory` | Isolierten cURL-/curl-multi-Transfer erzeugen |
| `TransferLease` | Lebensdauer, Promise, Cancellation und Ressourcenfreigabe kapseln |
| `DecisionReporter` | Reduzierte Entscheidungsereignisse; keine Rohrequests oder Secrets |
| `Typo3GuardRegistration` | Dokumentierter HTTP-handler-Erweiterungspunkt, Diagnose, Konfiguration |
| `VaultGuardAdapter` | Gemeinsamen Guard an die separate Vault-Factory anbinden |

## 2. Warum nicht nur ein IP-Filter vor `$next`?

Die normale Guzzle-Handlerwahl kann PHP-Streams auswählen; nr-vault dokumentiert dies ausdrücklich für `stream => true`. cURL-Pins kontrollieren außerdem weder einen beliebigen fremden Handler noch automatisch dessen Connection-/DNS-Sharing. [S05, S08, S12, S14]

Daher lautet die v1-Entscheidung: **Integration über Middleware, Versand über einen kontrollierten terminalen Adapter.** Nicht den Core ersetzen, sondern den letzten Versandpunkt des dokumentierten Stacks übernehmen. Eine bloße Vorprüfung mit anschließendem Aufruf eines beliebigen `$next` darf nicht als gleichwertiger Schutz ausgeliefert werden.

## 3. Die zwei Middlewarepositionen

Der aktuelle Core erzeugt zunächst den Guzzle-Standardstack, fügt gegebenenfalls seine kontextbezogene Allowlist ein und danach die konfigurierten eigenen Middlewares. [S01]

Vorgeschlagene Reihenfolge in Senderichtung:

```text
Guzzle-Defaults einschliesslich Redirect/Cookie/Body-Verarbeitung
  -> Core AllowedHostsMiddleware, soweit fuer den Kontext aktiv
    -> nr/http-guard-boundary
      -> bestehende Projekt-/Extension-Middlewares
        -> nr/http-guard-terminal
          -> eigener GuardedTransfer (cURL, isoliert)
```

**Boundary muss erster eigener Middlewareeintrag, Terminal letzter eigener Eintrag sein.** Die Einträge werden genau einmal registriert. `HTTP.handler` muss ein Middlewarearray sein. Ein bereits als Objekt gesetzter HandlerStack ist kein unterstützter Betriebszustand für Enforce.

Der terminale Eintrag verwendet im Enforce-Modus bewusst nicht den von Guzzle automatisch gewählten Standard-Leafhandler. Er delegiert an den eigenen kontrollierten Transport. Es darf kein benutzerdefinierter Middlewareeintrag hinter ihm liegen, weil dieser sonst übersprungen würde. Eine falsche Reihenfolge ist deshalb ein Konfigurationsfehler, kein Grund zum Umsortieren oder Ignorieren während eines laufenden Requests.

Die Registrierung muss im tatsächlichen TYPO3-Bootstrap beider Zielversionen verifiziert werden. Der Entwurf erfindet dafür kein nicht belegtes Core-Event. Der Installationsadapter registriert die beiden Einträge; nach abschließender Projektkonfiguration prüfen Diagnose und Requestpfad die effektive Reihenfolge. Kann sie durch weitere Extensions nicht gehalten werden, muss die endgültige Projektkonfiguration ausdrücklich nachgezogen oder die Integration revidiert werden. AP-01 liefert den getesteten Registrierungsweg und einen reproduzierbaren Bootstraptest als Pflichtartefakt.

Die Boundary hält die eingehende Origin in einem internen Envelope fest. Der Terminalguard lehnt eine Originänderung durch dazwischenliegende Middleware ab. So wird die zuvor im Core geprüfte Host-Allowlist nicht durch nachträgliches URL-Umschreiben ausgehebelt. Umleitungen sind weiterhin erlaubt, aber ausschließlich über reguläre Redirect-Antworten und erneute Stackdurchläufe.

Im Observe-Modus delegiert der Terminaleintrag an den bisherigen Leafhandler. Dieser Modus verändert den Versand nicht und besitzt folglich keine Pinning-/Transportschutzgarantie. Bei `disabled` sind beide Einträge transparent. Konfigurationsfehler bleiben diagnostizierbar, werden aber nicht mit einem geschützten Status verwechselt.

## 4. Ablauf eines Sendeversuchs

1. Boundary liest Modus und effektiven Registryzustand; sie erzeugt ein requestlokales Envelope mit kanonischer Anfangsorigin. Keine global veränderliche Variable für "aktuellen Kontext".
2. Bestehende Middlewares arbeiten; Originwechsel innerhalb dieses Bereichs ist nicht zulässig.
3. Terminalguard normalisiert den finalen Request und vergleicht ihn mit dem Envelope. Er liest nur vertrauenswürdig gebundene Grants.
4. Transport- und Optionengate prüfen cURL-Fähigkeiten, Proxyumgebung, rohe cURL-Optionen, Sharing, Streaming und Headerkonsistenz.
5. Endpoint-/Core-/Globalregeln begrenzen das Ziel. DNS oder die statische Zuordnung liefern Kandidaten; alle Kandidaten werden binär geprüft.
6. Ein erlaubter Versuch erhält einen `ConnectionPlan` mit Origin, Methode, kanonischen Adressen, Profil-ID, Policyrevision, Resolvergeneration und Ausstellungszeit.
7. Die Transferfactory erzeugt einen eigenen cURL-Transfer mit genau diesem Plan. Der Adapter erzeugt den Host-Port-Pin selbst und behält den kanonischen Zielhostname für SNI und HTTP-Host.
8. Promise/Response laufen durch die bestehenden Response-Middlewares zurück. Die Boundary prüft die danach tatsächlich vorliegende Redirect-Antwort, bevor Guzzles äußere Redirect-Middleware sie verarbeitet.
9. Ein neuer Hop beginnt wieder bei Schritt 1. EndpointGrant und gebundene Clientpolicy stammen aus den initialen vertrauenswürdigen Optionen, nicht aus Headern des entfernten Servers.

Die Boundary muss die Redirectentscheidung auf dem **finalen Response nach allen benutzerdefinierten Response-Middlewares** treffen. Andernfalls könnte eine nachträglich geänderte `Location`-Angabe die vorangegangene Prüfung entwerten.

## 5. Kontrollierter Transport

Version 1 verwendet pro eigenständigem Versuch einen isolierten `CurlMultiHandler` bzw. eine daraus gekapselte `TransferLease`; synchrone Aufrufe warten auf dieselbe Promise. Ein dedizierter synchroner Adapter ist nur zulässig, wenn er dieselben Isolationstests erfüllt. Keine vom Aufrufer gelieferte Handlerfactory oder Share-Handle.

Die Isolation umfasst DNS-Cache, Connection-Pool und aktive Transfers. Kein gemeinsamer Multi-Handle zwischen unabhängigen Versuchen in v1. Externes `transport_sharing` wird abgelehnt; der Bibliotheksadapter stellt die nicht geteilte Betriebsart versionsgerecht her. Damit wird nicht versucht, ein unsicheres Shared-Pool-Verhalten allein durch einen zusätzlichen `CURLOPT_RESOLVE`-Eintrag zu reparieren. Der zusätzliche Handshakeaufwand ist akzeptiert. [S12, S14]

Der einzige von außen nicht vorgebbare DNS-Pin wird aus dem ConnectionPlan erzeugt. Pro Host-Port-Kombination genau ein Eintrag mit allen zulässigen Adressen. IPv6-Adressen im Pin werden korrekt geklammert. Fehler beim Setzen einer Option müssen den Transfer abbrechen. Keine losen numerischen Optionen für nicht verfügbare cURL-Konstanten.

Direkte IP-URLs werden ebenso klassifiziert. Sie brauchen keine Namensauflösung, dürfen aber weder Proxy-, CONNECT_TO- noch Alternate-Service-Umleitungen aktivieren. Protokoll, Zielport und Original-Authority bleiben unverändert.

Der Adapter darf `on_stats` zur diagnostischen Nachprüfung der beobachteten Ziel-IP verwenden. **Das ist kein präventiver Schutz:** Der Request wurde dann bereits versandt. Ein Unterschied zum Plan ist ein Sicherheitsalarm und ein gescheiterter Abnahmetest, nicht der primäre Blockmechanismus. [S15]

## 6. Optionen

Versionsadapter für Guzzle 7 und 8 teilen eine normative Optionentabelle. Bekannte sichere Optionen wie Timeouts, Sink und TLS-Zertifikatspfade werden erhalten. Transportumleitungen wie eigene `handler`, Proxies, `CURLOPT_CONNECT_TO`, externe `CURLOPT_RESOLVE`, Unix-Sockets, eigene URL-/Host-/Portoptionen, Share-Handles und cURL-interne Redirects sind verboten. Unbekannte Optionen werden in Enforce nicht still durchgereicht.

Von der eigenen Bibliothek erzeugte interne Optionen werden nicht durch ein beliebiges Flag des Aufrufers legitimiert; sie entstehen ausschließlich nach erfolgreicher Planausstellung. Guzzle-8-Raw-Optionen sind restriktiver als historische Guzzle-7-Pfade; Adaptertests müssen diese Unterschiede abdecken. [S12]

Der Guard ändert nicht heimlich TLS-Verifikation oder allgemeine Anwendungstimeouts. `verify=false` wird deutlich diagnostiziert; seine Verwendung kann durch eine optionale strengere Betreiberpolicy verboten werden. Interne CA-Bundles und mTLS bleiben möglich. Der Netzwerkschutz ersetzt keine korrekte TLS-Konfiguration.

## 7. Redirects, Methoden und Secrets

Ohne ausdrücklichen Public-Fetch-Client gilt `same-origin`; ein Wechsel von Scheme, Host oder effektiver Portnummer wird vor dem nächsten Versand abgelehnt. Normale 30x-Antworten bei `allow_redirects=false` werden lediglich zurückgegeben; sie sind noch kein Zielkontakt. Die Boundary muss die Policyentscheidung nur erzwingen, wenn tatsächlich ein Follow aktiv ist.

Same-Origin-Redirects behalten Guzzles dokumentierte Methodensemantik. Nach einem Wechsel POST -> GET oder einem 307/308 wird die neue Methode bei Eintritt erneut gegen das Endpoint-Profil geprüft. Ein Profil kann Redirects vollständig verbieten. Maximale Hopzahl: höchstens Betreibergrenze und vorhandenes Requestlimit; Standard fünf. Da die äußere Guzzle-Redirect-Middleware ihre Optionen bereits erfasst, darf die Boundary kein wirkungsloses nachträgliches Clamp behaupten. Ein aktives Requestlimit oberhalb der Betreibergrenze wird vor dem ersten Versand abgelehnt; eigene Clientwrapper setzen das zulässige Limit vor Eintritt in Guzzle. Bei Betreiberlimit null muss Follow bereits deaktiviert sein. Ein kleineres Requestlimit bleibt unverändert. Die konkrete Zählsemantik ist pro Guzzle-Major zu testen. HTTPS -> HTTP ist auch im Public-Fetch-Client verboten.

Public-Fetch ist eine ausdrückliche separate API für GET/HEAD ohne Body und ohne Secrets. Sie sendet ausschließlich selbst erzeugte Header aus dem dokumentierten Allowset (`Accept`, `Accept-Encoding`, `User-Agent`); keine Cookies, kein `Authorization`, kein `Referer`, kein benutzerdefinierter API-Key-Header, keine TLS-Clientidentität. Damit wird kein unzuverlässiger Versuch unternommen, geheimen Inhalt in beliebigen Headers oder Bodies zu erkennen.

## 8. DNS und Cache

Positive DNS-Memoisierung: maximal fünf Sekunden und maximal 32 Hosts als Default, begrenzt durch einen verfügbaren kleineren TTL; TTL 0 wird nicht gecacht. Schlüssel: kanonischer Host, Resolveridentität und Resolverkonfiguration. Negative Antworten werden in v1 nicht gecacht. Eine Deadline oder ein Policywechsel darf keinen alten positiven Sicherheitsentscheid wiederverwenden.

Der Cache enthält nur Adressdaten, niemals "dieser Host ist erlaubt". Bei jedem Versuch werden alle cached IPs erneut klassifiziert und gegen die aktuelle Policy geprüft. Alle anderen Ausnahmen und Profile bleiben requestlokal. Ein ConnectionPlan wird nicht persistent gespeichert oder an andere Requests weitergereicht.

Der erste DNS-Adapter darf die nr-vault-Struktur nutzen, muss aber TTL-/CNAME-/Fehlerdaten getrennt erfassen. Der synchrone PHP-DNS-Aufruf besitzt keine zuverlässige per-call-Abbruchgarantie. Gemessene DNS-Zeit, Betriebssystemresolvergrenzen und Workerlimits sind deshalb Teil der Betriebsabnahme. Ein behaupteter harter 2-Sekunden-Timeout ohne unterbrechbaren Resolver ist unzulässig. Ein asynchroner, hart deadlinefähiger Resolver ist eine spätere austauschbare Implementierung, nicht still vorausgesetzt.

## 9. Streaming, Cancellation und PSR-18

Der globale Adapter unterstützt in v1 normale gebufferte RequestFactory-/PSR-18-Aufrufe. `stream => true` wird abgelehnt, nicht ignoriert oder transparent in einen schwächeren Pfad umgewandelt.

Vaults vorhandenes `sendStreaming()` ist davon zu unterscheiden: Es treibt bereits curl-multi ohne `stream => true`. Diese API soll über den expliziten Vault-Adapter erhalten bleiben. Die Bibliothek liefert dafür eine interne Transferlease; Secrets bleiben außerhalb ihrer Verantwortung. Kein Benutzer des credentialtragenden Vault-Clients erhält direkten Zugriff auf Transport, Promise oder rohe cURL-Optionen. [S08]

Jeder aktive Transfer besitzt genau einen Owner. Settlement, Cancellation, Body-close und Fehler geben Ressourcen idempotent frei. Aufträge werden nicht über den Request hinaus im Hintergrund fortgesetzt. Ein teilweise gelesener, fehlgeschlagener Stream wird nicht als vollständiger EOF-Erfolg gemeldet. Bisherige Vault-Buffer-, Idle- und Cancellation-Regeln bleiben durch Regressionstests abgesichert.

## 10. Bekannte Grenzen

Ein pro Request komplett ersetzter Handler kann die Middleware umgehen, bevor sie überhaupt aufgerufen wird. Dasselbe gilt für fremde SDKs und direkte Sockets. Diagnose und statische Integrationssuche melden solche Wege; sie können nicht aus der Middleware heraus lückenlos verhindert werden. Das Produkt darf diesen Unterschied nicht durch das Wort "global" verbergen.
