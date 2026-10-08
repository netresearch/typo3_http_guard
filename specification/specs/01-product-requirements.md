# 01 - Produkt- und Anforderungsspezifikation

Stand: 2026-10-08. Status: vorgeschlagen. Quellenkennungen werden in Dokument 08 aufgelöst.

## 1. Problem

TYPO3 erlaubt eigene Guzzle-Middlewares über `TYPO3_CONF_VARS.HTTP.handler`. Die Core-Host-Allowlist ist kontextbezogen; sie ersetzt keine Prüfung der tatsächlich verwendeten Ziel-IP. nr-vault besitzt bereits eine eigene SSRF-/DNS-Pinning-Implementierung, verwendet aber einen separaten Handlerstack. Eine Registrierung am Core allein vereinheitlicht diese Transportwege deshalb nicht. [S01-S05]

Gesucht ist ein zentraler Schutz für bereits bestehende Standardaufrufe und eine gemeinsame Sicherheitsimplementierung für bewusst separat aufgebaute Clients. Nicht gesucht ist eine neue Secrets-Verwaltung.

## 2. Ziele

1. Unzuverlässige Ziel-URLs dürfen über erfasste HTTP-Clients keine nicht freigegebenen lokalen, privaten oder besonderen Adressbereiche erreichen.
2. DNS-Prüfung und tatsächliche Verbindung müssen dieselbe Menge freigegebener IP-Adressen verwenden.
3. Bewusst konfigurierte interne Integrationen bleiben möglich, aber nur mit zweckgebundenen Freigaben.
4. Schutz darf nicht von zufälliger Handlerwahl, Redirects, Proxies, Cachezustand oder einem stillen Fallback abhängen.
5. Die Implementierung bleibt ohne nr-vault benutzbar; nr-vault verliert keine bestehende Fachfunktion.

## 3. Nichtziele

Kein Schutz gegen beliebigen bösartigen PHP-Code; keine Erfassung direkter cURL-, Stream-, Socket- oder SDK-Verbindungen außerhalb des integrierten Transports; keine Netzwerkfirewall; keine Content-Sandbox; kein Ersatz für Anwendungsauthentifizierung. Kein Schutz vor jeder Form von SSRF gegen öffentliche Ziele, öffentliche Reverse-Proxies oder absichtlich freigegebene interne APIs. Keine neue Credential-Injection. Kein Rust-/FFI-/Sidecar-Zwang. Kein generischer HTTP-Proxy und kein HTTP/3 in Version 1.

## 4. Zielgruppen und Fälle

| Rolle | Fall | Erwartetes Ergebnis |
|---|---|---|
| Integrator | Extension ruft eine normale öffentliche HTTPS-API über RequestFactory auf | Ohne Änderung der Aufrufstelle nutzbar |
| Betreiber | URL-Importer wird auf eine interne IP gelenkt | Ablehnung vor Kontakt zum Ziel |
| Betreiber | ERP ist nur intern erreichbar | Explizit gebundener ERP-Client funktioniert; URL-Importer erhält dadurch keine Freigabe |
| Extension-Autor | Eigener PSR-18- oder Guzzle-Client | Bewusste Integration über dokumentierten Adapter |
| nr-vault-Maintainer | Credential-geschütztes Streaming | Gemeinsame Netzwerkpolicy, unveränderte Credential-/Audit-Verantwortung |
| Betrieb | Altinstallation nutzt Proxy oder StreamHandler | Prüfbarer Kompatibilitätsbefund; kein vermeintlicher Schutz trotz Bypass |

## 5. Normative Anforderungen

Jede Anforderung ist durch die Testmatrix in Dokument 06 zu belegen.

| ID | Anforderung |
|---|---|
| HG-001 | Die Extension MUSS den dokumentierten ausgehenden Guzzle-Middleware-Erweiterungspunkt verwenden; keine eingehende PSR-15-Middleware. |
| HG-002 | Im geschützten Standardstack MUSS jeder tatsächliche Sendeversuch den Guard unmittelbar vor dem kontrollierten Transport passieren. |
| HG-003 | Der Standardmodus MUSS `enforce` sein; fehlende oder ungültige Konfiguration DARF NICHT zu offenem Versand führen. |
| HG-004 | Neue Installationen MÜSSEN ohne interne Freigaben starten. Bestehende Projektkonfiguration DARF NICHT automatisch in Freigaben umgedeutet werden. |
| HG-005 | Version 1 MUSS TYPO3 13.4 und 14.3 als getrennte Testziele behandeln; konkrete Mindestpatchstände werden durch Composer und CI belegt. |
| HG-006 | Die gemeinsame Bibliothek DARF keine TYPO3- oder nr-vault-Abhängigkeit benötigen. |
| HG-007 | URLs MÜSSEN vor Policy- und DNS-Auswertung einheitlich und strikt normalisiert werden. |
| HG-008 | Nur absolute HTTP-/HTTPS-Ziele mit gültigem Host und Port sind erlaubt. Userinfo, Fragmente, Zone-IDs und uneindeutige numerische IP-Formen MÜSSEN abgelehnt werden. |
| HG-009 | URI-Authority und effektiver HTTP-Host MÜSSEN übereinstimmen; beliebige Host-Header-Umleitungen sind nicht Teil des Standardprodukts. |
| HG-010 | IPv4, IPv6 und IPv4-mapped IPv6 MÜSSEN binär und mit derselben Policy klassifiziert werden. |
| HG-011 | Private, lokale, Link-Local-, Multicast-, Dokumentations-, Benchmark- und weitere ausgeschlossene Spezialbereiche MÜSSEN vom Public-Profil gesperrt werden. |
| HG-012 | Zusätzliche Betreiber-Deny-CIDRs MÜSSEN möglich sein und Vorrang vor Freigaben haben. |
| HG-013 | Jeder verwendbare A-/AAAA-Kandidat MUSS geprüft werden. Ein verbotener Kandidat in einer Antwort MUSS den gesamten Versuch ablehnen. |
| HG-014 | Leere, fehlerhafte oder nicht verwertbare Auflösung MUSS geschlossen scheitern; eine Endpoint-Freigabe DARF dies nicht umgehen. |
| HG-015 | Der Transport MUSS ausschließlich den validierten ConnectionPlan verwenden; eine unabhängige Zielauflösung ist verboten. |
| HG-016 | Mehrere zulässige IPs MÜSSEN in einem gültigen Pin pro Host-Port-Paar erhalten bleiben; Dual-Stack-Fallback bleibt auf diese Menge beschränkt. |
| HG-017 | Interne Ausnahmen MÜSSEN Origin, Port, Methoden, erlaubte IP-Mengen und einen explizit gebundenen Client umfassen. |
| HG-018 | Eine einfache URL, ein frei kopierter Request-Header oder ein vom Benutzer gesetzter Kontextstring DARF keine interne Freigabe aktivieren. |
| HG-019 | Core-Allowlist, globale Verbote und Endpoint-Policy MÜSSEN kumulativ gelten. Keine engere Policy darf durch eine breitere ersetzt werden. |
| HG-020 | Sicherheitsrelevante rohe Transportoptionen von Aufrufern MÜSSEN abgelehnt werden; der Guard erzeugt seine Optionen selbst. |
| HG-021 | Der geschützte Transport MUSS cURL/curl-multi verwenden. Fehlende Unterstützung oder unbekannte Handler MÜSSEN im Enforce-Modus vor Versand fehlschlagen. |
| HG-022 | Explizite und über die Umgebung geerbte Proxykonfiguration MUSS erkannt werden. Version 1 darf nicht über einen Proxy senden und darf ihn nicht still umgehen. |
| HG-023 | Jeder Redirect MUSS erneut vollständig geprüft werden; cURL-eigene, am Stack vorbeilaufende Redirects sind verboten. |
| HG-024 | Redirects mit Credentials MÜSSEN derselben Origin treu bleiben; bei generischem Standardclient gilt konservativ Same-Origin. |
| HG-025 | Jeder Retry MUSS erneut durch die Policy laufen. Policyablehnungen sind nicht retryfähig; Retry-Budgets darf der Guard nicht selbst unbemerkt erhöhen. |
| HG-026 | Connection- und DNS-Caches MÜSSEN zwischen unabhängigen Transfers isoliert sein. Gemeinsame cURL-Share-Handles sind in Version 1 verboten. |
| HG-027 | DNS-Memoisierung DARF nur geprüfte Adressen wiederverwenden, MUSS begrenzt sein und MUSS pro Verwendung erneut gegen die aktuelle Policy prüfen. |
| HG-028 | `stream => true` DARF NICHT auf PHP-Streams ausweichen. In Version 1 des globalen Adapters wird die Option ausdrücklich abgelehnt. |
| HG-029 | Ein bewusst integrierter curl-multi-Streamingadapter MUSS denselben Guard und dieselbe Pinning-Invariante verwenden und Cancellation erhalten. |
| HG-030 | Bei Cancellation oder Fehlern MÜSSEN Handler, Sockets und Response-Buffer freigegeben werden; keine hängenden Hintergrundtransfers. |
| HG-031 | Die Guard-API MUSS typisierte, stabile Ablehnungsgründe und eine Promise-/PSR-18-konforme Fehleroberfläche anbieten. |
| HG-032 | Logs DÜRFEN keine Bodies, Credentials, vollständigen URLs, Querystrings oder rohe Requests enthalten. |
| HG-033 | Prüfmodus MUSS sichtbar als nicht schützend gekennzeichnet sein. `enforce`, `observe` und `disabled` dürfen nicht verwechselt werden. |
| HG-034 | Ein Diagnosekommando MUSS Konfiguration, Reihenfolge, Laufzeitfähigkeiten, Proxyzustand und Abdeckungsgrenzen ohne Probezugriffe anzeigen. |
| HG-035 | Eine URL-Prüfung ohne Versand MUSS möglich sein; sie ist keine später wiederverwendbare Autorisierung. |
| HG-036 | nr-vault MUSS über einen eigenen Adapter migriert werden; seine separate Factory DARF NICHT als automatisch global geschützt ausgegeben werden. |
| HG-037 | Vault-Integration MUSS Credential-Injection, Secret-Zugriffskontrolle, Auditing, OAuth-Tokenabruf, Timeout-, Streaming- und Cancellation-Verhalten erhalten. |
| HG-038 | Bestehende flache Vault-Allowlist-Einträge DÜRFEN nicht automatisch globale oder uneingeschränkte interne Freigaben werden. |
| HG-039 | Sicherheitskritische Ablehnungen MÜSSEN mit einem nachweislich nicht kontaktierten Ziel getestet werden; eine Exception allein ist kein Nachweis. |
| HG-040 | Gleichzeitige Requests und langlebige CLI-Prozesse MÜSSEN auf Cross-Request-, Cache- und Policy-Lecks getestet werden. |
| HG-041 | Bibliotheks-, TYPO3- und Vault-Tests MÜSSEN denselben normativen Sicherheitskorpus verwenden. |
| HG-042 | Unsupported-Konfigurationen MÜSSEN sichtbar fehlschlagen. Die Dokumentation DARF keine weitergehende Abdeckung als die getestete behaupten. |
| HG-043 | Neue Defaults oder eine Entfernung des Vault-Legacyverhaltens MÜSSEN als Verhaltensänderung migriert und versioniert werden. |
| HG-044 | Ressourcenverbrauch und Zeitverhalten MÜSSEN dokumentierte Grenzen besitzen; synchrone DNS-Auflösung DARF nicht fälschlich als hart deadlinefähig dargestellt werden. |
| HG-045 | Jeder Security-Fix MUSS eine reproduzierbare Regression und eine Auswirkungsprüfung auf beide Integrationen erhalten. |

## 6. Nichtfunktionale Ziele

**Sicherheit:** kein offener Fallback im geschützten Pfad; alle P0-Tests aus 06 sind Freigabebedingung. **Wartbarkeit:** Policies, Adressklassifikation und Transportadapter sind getrennt testbar. **Beobachtbarkeit:** Entscheidung, Regel-ID und Modus sind erkennbar, Geheimnisse bleiben unprotokolliert. **Performance:** keine Cross-Request-Verbindungspools in v1; zusätzliche Handshakes sind eine bewusste Kostenentscheidung, kein versteckter Defekt. Benchmarks vergleichen gleichen Payload, gleiche DNS-Antworten und gleiche Hardware. Ein absolutes Latenzversprechen ohne Messung wird nicht gemacht.

Für reine Normalisierung/Klassifikation/Policybewertung ohne DNS, Netzwerk und Logging gilt als Abnahmeziel: p95 höchstens 2 ms auf dem benannten CI-Referenzsystem bei maximal 64 Adressen und 128 Endpoint-Profilen. Kein harter SLO für beliebige Hardware. Speichercaches sind konfigurationsseitig begrenzt; Request-/Response-Bodies werden vom Guard nicht dupliziert.

## 7. Unterstützungsmatrix

Planungsziele: PHP 8.2, 8.3, 8.4 und 8.5; TYPO3 13.4/14.3; Guzzle 7 und 8 samt den jeweils auflösbaren PSR-7-/Promise-Versionen. Der aktuelle TYPO3-14.3-Quellstand erlaubt Guzzle `^7.15.2 || ^8.0`; ein nur gegen Guzzle 7 entwickelter Adapter reicht deshalb nicht. [S13]

Eine Kompatibilitätszusage entsteht erst nach erfolgreichen Tests für die konkrete Kombination. Betriebssystembaseline: Linux mit cURL/curl-multi; andere Plattformen werden erst nach derselben Wire-Test-Suite freigegeben. cURL >= 7.59.0 ist die funktionale Untergrenze für Multi-Address-Pins, nicht die Aussage, dass jede solche Version sicher ist. Die tatsächlich zugelassene libcurl-Version muss sicherheitsgepflegt sein, einschließlich nachvollziehbarer Distributor-Backports. [S09]
