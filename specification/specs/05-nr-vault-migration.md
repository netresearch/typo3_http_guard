# 05 - nr-vault: Befund, Wiederverwendung und Migration

Stand: 2026-10-08. Untersucht: `netresearch/t3x-nr-vault`, Commit `5a070c396a614e5b05f63d79fa564c3748cf21eb` vom 7. Oktober 2026. Die Aussagen beschreiben diesen Quellstand, nicht pauschal jede veröffentlichte Version.

## 1. Was bereits vorhanden ist

| Befund | Quelle | Konsequenz |
|---|---|---|
| `SecureHttpClientFactory::create()` baut einen eigenen HandlerStack und registriert `ssrf-dns-pin`. | S04 | Gute technische Vorlage; globale Core-Middleware erfasst diesen Stack nicht automatisch. |
| `createCancellable()` verwendet dieselbe gehärtete Optionsaufbereitung und einen curl-multi-Pfad. | S04 | Cancellation nicht als separaten, schwächeren Sicherheitszweig neu bauen. |
| `isHostAllowed()` und die Middleware normalisieren Hostangaben und prüfen IP-/DNS-Ergebnisse. | S04-S05 | Normalisierung und Adressklassifikation sind Kandidaten für gemeinsame Komponenten. |
| Eine gefährliche Adresse unter mehreren DNS-Antworten führt zur Ablehnung; zulässige Adressen werden in einem Multi-Address-Pin zusammengefasst. | S05 | Dual-Stack-/Multi-Address-Verhalten und Tests übernehmen, nicht auf erste/letzte IP verkürzen. |
| Fehlende verwertbare Auflösung scheitert seit ADR-038 geschlossen, ausgenommen exakt freigegebene Hosts. | S06 | Richtige grundsätzliche Erkenntnis; die verbleibende Ausnahme wird im neuen Guard nicht übernommen. |
| Exakte flache `allowed_hosts`-Einträge können private Netze und fehlende DNS-Ergebnisse bewusst freigeben. Wildcards haben dieses Privileg nicht. | S04-S06 | Das sind betriebliche Ausnahmen, keine sichere allgemeine Public-Internet-Policy. |
| Fehlendes ext-curl wird gewarnt, der Client darf dennoch auf einen schwächeren Streampfad ausweichen. | S05 | Im neuen Enforce-Modus verboten; nicht still in nr-vault rückportieren. |
| `stream => true` wird als Pinninggrenze erkannt; die reine Factoryoberfläche kann den Fall trotzdem an den Streampfad weiterreichen. | S05 | Geschützte generische Adapter müssen diesen Optionspfad ablehnen. |
| `sendStreaming()` nach ADR-039 treibt den gepinnten curl-multi-Transfer, ohne die gefährliche `stream`-Option zu verwenden. | S08 | Diese API ausdrücklich erhalten; "Streams verbieten" darf nicht "Vault-Streaming abschalten" bedeuten. |
| Positives DNS-Memo: fünf Sekunden, bis zu 32 Hosts, Wiederverwendung abhängig davon, ob der Pin tatsächlich angewendet wird. | S04-S05 | Sinnvolle Ausgangsidee; Cache enthält im Zielprodukt nur Adressen und ist policyunabhängig. |
| Composer erlaubt PHP ^8.2, TYPO3 ^13.4 oder ^14.3 und Guzzle ^7.10 oder ^8.0; ext-curl ist nur vorgeschlagen. | S16 | Neue Capability-Anforderungen sind eine bewusste Kompatibilitätsänderung. |

Die genannten Schutzmechanismen wurden aus Quellcode und ADRs nachvollzogen. Vorhandene Testberichte in ADRs sind Aussagen des Repositories; sie wurden hier nicht eigenständig erneut ausgeführt.

## 2. Unterschiede, die nicht unter den Tisch fallen dürfen

### 2.1 Zwei unterschiedliche `allowed_hosts`-Semantiken

Der untersuchte Core liest `HTTP.allowed_hosts[context]` als Liste. Die untersuchte Vault-Factory liest `HTTP.allowed_hosts` selbst und verarbeitet darin Stringwerte als flache Liste. Damit sind ein Kontextarray des Core und eine flache Vault-Freigabe nicht austauschbar. [S01, S05]

**Neue Entscheidung:** eigener Namensraum `EXTCONF.nr_http_guard`, keine Umdeutung bestehender Core-Schlüssel. Die Migration berichtet flache Vault-Einträge und verschachtelte Core-Einträge getrennt. Ein Core-Kontext ist zudem nicht automatisch als Metadatum in fremden Middlewares verfügbar.

### 2.2 Ein freigegebener Host ist kein Freibrief

Im neuen Guard muss auch ein privater Endpoint auf konkrete erlaubte Adressen aufgelöst und gepinnt werden. Eine statische Zuordnung ersetzt bei lokalen Namen fehlende DNS-Daten. Ein Lookupfehler wird nicht mit "der Betreiber vertraut diesem Host" überbrückt.

### 2.3 Proxyunterstützung ist nicht automatisch Ziel-Pinning

Vault berücksichtigt Proxykonfiguration. Ein HTTP-Proxy kann aber die Zielauflösung selbst vornehmen; lokale `CURLOPT_RESOLVE`-Einträge beweisen dann keine Kontrolle der finalen Origin-IP. [S04, S17] Das ist eine zu prüfende Transportgrenze, hier kein pauschal nachgewiesener Exploit gegen alle Vault-Aufrufpfade.

Das neue Produkt lehnt Proxypfade in Enforce v1 ab. Ein zukünftiger Proxyadapter braucht ein eigenes Threat Model und Wire-Tests am Proxy und am Ziel. Private Proxies als Transportendpunkt sind von privaten Origin-Zielen zu unterscheiden; beide einfach in dieselbe Allowlist zu werfen ist keine Lösung.

### 2.4 Streaming und Cancellation sind bereits Fachverträge

ADR-039 beschreibt unter anderem inkrementelle Auslieferung, begrenzte Puffer, korrekte Proxy-/Origin-Headerinterpretation, Fehlermeldung bei Teilübertragungen und Lebensdauer des Transports. Eine Extraktion nur der "HTTP-Erfolgspfade" würde diese Verträge riskieren. [S08]

## 3. Wiederverwendungsmatrix

| Bestandteil | Vorgehen |
|---|---|
| Host-/IP-Normalisierung | Als Startpunkt extrahieren, gegen erweiterten Korpus und cURL-Parservergleich prüfen |
| IPv4-/IPv6-CIDR-Logik | In `AddressClassifier` überführen; nicht nur PHP-Filterflags als Gesamtlösung verwenden |
| DNS-Resolverinterface | Fachidee erhalten; Fehler, Quelle, TTL und Aliasdaten ausdrücklicher modellieren |
| Multi-Address-Pinaufbau | Verhalten übernehmen; bestehende Caller-Pins nicht mehr zusammenmischen |
| DNS-Memoisierung | Adressdaten wiederverwenden, keine Freigaben cachen |
| `isHostAllowed()` | In Vault als kompatible Oberfläche zunächst erhalten; im neuen Pfad an dieselbe Policyengine delegieren |
| Globales `allowed_hosts`-Opt-in | Nicht als neue Guardpolicy übernehmen; Migration in explizite Profile |
| Degraded Stream-Fallback | Nicht in den neuen geschützten Pfad übernehmen |
| Credential-Injection und OAuth | In Vault belassen; sowohl Token- als auch Zielrequest absichern |
| StreamingSink, Body-/Cancellation-Lebenszyklus | In Vault belassen, soweit nicht eine gezielte gemeinsame Transportextraktion nach Tests gerechtfertigt ist |
| Audit und Secret-Zugriffsprüfung | Vollständig in Vault belassen; kein neues paralleles Secrets-Audit |
| Unit-/Wire-Testideen | Gemeinsamer Sicherheitskorpus plus Vault-spezifische Regressionen |

## 4. Migrationsphasen

### M1 - Charakterisierung, noch keine Verhaltensänderung

Aktuelle Factory-/Vault-Verträge in Regressionstests festhalten. Alle Aufrufpfade inventarisieren: normaler Send, OAuth-Tokenrequest, cancellable, streaming, nutzerinjizierter Client, ohne curl, `stream`-Option, Proxy, direkte Factorynutzung. Quellcode und Tests sind zu versionieren; keine Aussage "alles abgesichert" allein aus der Existenz der Factory ableiten.

### M2 - Gemeinsame Bibliothek und unabhängige TYPO3-Extension

Bibliothek unter eigenem Paket entwickeln; globalen Guard unabhängig von einer installierten Vault-Extension testen. Das installiert kein Secrets-System. Vault bleibt zunächst unverändert. Temporär parallele Implementierungen sind sichtbar und zeitlich durch den Migrationsplan begrenzt.

### M3 - Expliziter geschützter Vault-Pfad

Die Vault-Factory erhält einen bewussten internen Adapter zur gemeinsamen Bibliothek. Ein opt-in-Pilot darf in einer kompatiblen Erweiterung erfolgen, sofern bestehende Defaults und öffentliche Signaturen unverändert bleiben. Die öffentlichen APIs `sendRequest`, `sendCancellable` und `sendStreaming` behalten ihre Fachsemantik. Bei Wahl des neuen Guardpfades gilt dessen Fail-closed-Vertrag; er darf nicht bei Fehlern auf die alte Factory zurückfallen.

Die Policy von Tokenendpoint und Resourceendpoint kann verschieden sein; beide sind getrennt zu binden. Ein Grant für den Resourceendpoint berechtigt nicht automatisch zum internen OAuth-Tokenendpoint und umgekehrt.

### M4 - Änderung des Standardpfads mit klarer Versionierung

Erst nach Pilot, Migrationsleitfaden und abgearbeiteten Kompatibilitätsbefunden wird der strengere Pfad Standard. Fehlendes ext-curl, Proxybetrieb, bisherige Allowlist-Ausnahmen und ein ausschließlich über NSS bekannter Host sind dokumentierte Breaking Changes. Die Entfernung der alten Semantik gehört in eine entsprechend kommunizierte inkompatible Version oder eine ausdrücklich freigegebene Sicherheitsänderung, nicht in ein als rein internes Refactoring beschriebenes Update.

### M5 - Entfernen der doppelten Implementierung

Nach Ende des Migrationsfensters alte Klassifikations-/Pinninglogik aus Vault entfernen. Bestehende öffentliche Kompatibilitätsmethoden dürfen an die gemeinsame Engine delegieren. CI prüft, dass nicht erneut eine zweite private Netzklassifikation in Vault entsteht.

## 5. Freigabebeispiel

Vorher: ein flacher exakter Host erlaubt einen internen Dienst und kann ungeprüfte Resolverpfade einschließen.

Nachher: Profil `erp-orders` mit Origin `https://erp.internal.example:8443`, Methode GET/POST, IP `10.23.4.12/32`, statischer Hostzuordnung und nur im ERP-Service injiziertem Client. Kein automatischer URL-Matcher im globalen Guard. Credentialreferenzen verbleiben in der Vaultkonfiguration.

Eine automatisierte Migration kann einen **nicht aktiven Vorschlag** mit ermittelbaren Daten erzeugen. Fehlende Ports, Adressen, Methoden, Zuständigkeit und Verwendungskontext werden als offen markiert; sie werden nicht geraten. Der Betreiber prüft und aktiviert den Vorschlag explizit.

## 6. Rücknahme

Rollback bedeutet Rückkehr zu einer dokumentierten alten Sicherheitslage, nicht Erhalt des neuen Schutzversprechens. Der neue Guard selbst besitzt keinen automatischen Legacy-Fallback. In einem Vault-Pilot kann die deployte Factoryauswahl bewusst zurückgesetzt werden; Logs und Betriebsmeldung müssen den reduzierten Schutz sichtbar machen. Keine Änderung an gespeicherten Secrets und kein Datenbankrollback sind für die reine Guardintegration erforderlich.
