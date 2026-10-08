<a id="doc-readme"></a>

# HTTP Guard: Spezifikation und Architekturentscheidungen

**Stand:** 8. Oktober 2026  
**Dokumentversion:** 0.1 - vollständiger Entwurf zur Implementierungsfreigabe  
**Status aller neuen ADRs:** Vorgeschlagen; nicht als bereits beschlossen oder umgesetzt zu verstehen.  
**Arbeitstitel:** HTTP Guard. Paketnamen in diesem Entwurf sind Vorschläge, keine Aussage über registrierte oder verfügbare Pakete.

## Entscheidung in einem Satz

Eine eigenständige TYPO3-Extension sichert den Standard-HTTP-Stack zentral ab; eine gemeinsam genutzte PHP-Bibliothek setzt Zielprüfung und verbindungsgebundenes DNS-Pinning durch. nr-vault verwendet dieselbe Bibliothek, bleibt aber verantwortlich für Secrets, Credential-Injection, Audit und seine Streaming-/Cancellation-APIs.

**Das Schutzversprechen gilt nur für erfasste Transportwege.** Die Lösung ist keine Firewall, keine PHP-Sandbox und keine Autorisierung für beliebige Ressourcen auf einem erlaubten Zielserver.

## Dokumente

| Dokument | Inhalt |
|---|---|
| [01 Produkt und Anforderungen](#doc-specs-01-product-requirements) | Ziele, Umfang, normative Anforderungen, Kompatibilität |
| [02 Sicherheitsmodell und Policies](#doc-specs-02-security-model) | Angreifer, Vertrauensgrenzen, IP-Klassen, Freigabesemantik |
| [03 Architektur und Laufzeit](#doc-specs-03-architecture) | Middleware, kontrollierter Transport, DNS, Redirects, Lebenszyklus |
| [04 Konfiguration und APIs](#doc-specs-04-configuration-and-api) | Vorgeschlagenes Schema, Defaults, Beispiele, Fehler, CLI |
| [05 nr-vault und Migration](#doc-specs-05-nr-vault-migration) | Quellcodebefund, Wiederverwendung, Unterschiede, BC-Plan |
| [06 Tests und Abnahme](#doc-specs-06-verification) | Testmatrix, Anforderungen-Tests-Zuordnung, Release-Gates |
| [07 Lieferung und Betrieb](#doc-specs-07-delivery-and-operations) | Arbeitspakete, Betriebsabläufe, Rollout und Rücknahme |
| [08 Evidenz und Quellen](#doc-specs-08-evidence-and-sources) | Untersuchte Quellen, Commit-Stände, Grenzen der Untersuchung |
| [ADR-Index](#doc-adr-readme) | 14 einzeln begründete Architekturentscheidungen |

## Lesereihenfolge

Für die Freigabe: Dokumente 01, 02, 05 und ADR-Index. Für die Umsetzung: alle Dokumente; insbesondere die Transport- und Reihenfolgeinvarianten aus 03 und die Abnahme aus 06. Für den Betrieb: 04 und 07.

## Verbindlichkeit

**MUSS**, **DARF NICHT** und **SOLL** beschreiben Anforderungen des vorgeschlagenen Produkts, keine Eigenschaften einer bereits existierenden Extension. Bei Widersprüchen gilt: Sicherheitsinvarianten aus 02 vor Beispielen; normatives Schema aus 04 vor Kurzbeispielen; ADRs erklären Entscheidungen, ersetzen aber keine Anforderungen.

Geänderte Entscheidungen erhalten einen neuen ADR mit ausdrücklicher Ablösung des bisherigen. Sicherheitsversprechen dürfen nicht durch einen beiläufigen Konfigurationsschalter erweitert werden. Ungeprüfte Transportwege werden nicht als geschützt ausgewiesen.

## Lieferumfang und Grenzen dieses Entwurfs

Der Entwurf beruht auf einer gezielten Quellcode- und Dokumentationsprüfung, insbesondere von `netresearch/t3x-nr-vault` am Commit `5a070c396a614e5b05f63d79fa564c3748cf21eb`. Er ist keine vollständige Sicherheitsprüfung dieses Repositories. Es wurden weder eine neue Extension implementiert noch nr-vault-Tests oder Netzwerktests ausgeführt. Anforderungen und Tests unten sind die umzusetzenden bzw. zu erbringenden Nachweise.

Die technische Integrationsprobe in Arbeitspaket AP-01 ist ein Freigabegate: insbesondere letzte Middlewareposition, kontrollierter cURL-Transport, Guzzle-7/8-Verhalten und Isolation gleichzeitiger Transfers müssen vor der eigentlichen Implementierung bewiesen werden. Ein negatives Ergebnis verlangt eine ADR-Revision, keinen stillen Fallback.


---

<a id="doc-specs-01-product-requirements"></a>

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


---

<a id="doc-specs-02-security-model"></a>

# 02 - Sicherheitsmodell und Policies

Stand: 2026-10-08. Status: vorgeschlagen.

## 1. Angreifer und schützenswerte Ressourcen

Angreifer können unzuverlässige URLs, importierte Inhalte, Redirect-Antworten und die DNS-Zone ihrer Zielhostnamen kontrollieren. Sie können Anfragen wiederholen und parallele Abläufe auslösen. Sie besitzen keine Kontrolle über deployte PHP-Konfiguration, installierte Erweiterungen, den Betriebssystemresolver oder den Kernel. Ein kompromittierter DNS-Resolver darf trotzdem keine nicht freigegebenen privaten Adressen in den erfassten Transport einschleusen.

Zu schützen sind lokale Webdienste, interne APIs, administrativ erreichbare Ports, Cloud-Metadaten, Credentials in ausgehenden Requests sowie die Verfügbarkeit des TYPO3-Prozesses. Die Anwendung bleibt verantwortlich für Nutzerrechte, Rate Limits und fachliche Zielautorisierung.

## 2. Vertrauensgrenzen

```text
Unzuverlaessige URL / Content / Redirect
             |
             v
Anwendung: darf diese Aktion stattfinden?
             |
             v
Core-Host-Allowlist + Guard-Policy
             |
             v
Kanonisches Ziel + validierte Adressmenge
             |
             v
Unveraenderlicher ConnectionPlan
             |
             v
Kontrollierter, isolierter cURL-Transport
             |
             v
Netzwerk / Firewall / Zielserver
```

Konfiguration und explizit gebundene Client-Services liegen auf der vertrauenswürdigen Seite. PSR-7-Requests haben keine sicheren benutzerdefinierten Attribute für diese Aufgabe; ein HTTP-Header ist keine Capability. Eine Guzzle-interne Objektoption kann die Bindung transportieren, darf aber niemals als Header gesendet oder aus Benutzereingaben rekonstruiert werden.

## 3. Sicherheitsinvarianten

**INV-01 - Keine ungeprüfte Adresse:** Jeder Verbindungsversuch nutzt ausschließlich Adressen des für genau diesen Versuch validierten Plans.

**INV-02 - Vollständige Zielbindung:** Scheme, kanonischer Host, effektiver Port, Methode, Endpoint-Profil und Policyrevision werden gemeinsam bewertet. Ein Wechsel invalidiert den alten Plan.

**INV-03 - Fail closed:** Fehlerhafte URL, fehlende verwertbare Adressen, nicht unterstützter Transport, Reihenfolgefehler oder ungültige Policy führen im Enforce-Modus vor Zielkontakt zur Ablehnung.

**INV-04 - Keine implizite Ausnahme:** Interne Freigaben gelten nur für explizit gebundene Aufrufpfade. Eine bloße Übereinstimmung von Zielhostname und globaler Liste reicht nicht.

**INV-05 - Jede Wiederholung ist neu:** Redirects und Retries durchlaufen dieselben Invarianten; es gibt keinen einmalig ausgestellten URL-Freibrief.

**INV-06 - Policykomposition verengt:** Zusätzliche Einschränkungen werden geschnitten, nicht ersetzt. Harte Verbote haben Vorrang.

**INV-07 - Kein Zustandstransfer:** Eine interne Freigabe, ein DNS-Pin oder eine Verbindung darf nicht auf einen anderen Request, anderen Client oder andere Policy übergehen.

**INV-08 - Ehrliche Abdeckung:** Kein geschütztes Label für nicht erfasste Clients, Observe-Modus, deaktivierten Guard oder ungeprüfte Proxy-/Stream-/Custom-Handler-Pfade.

## 4. Policy-Auswertung

Die Entscheidung wird in dieser Reihenfolge getroffen:

1. Syntax, Schema, Host-Header-Konsistenz und Transportfähigkeit prüfen.
2. Betreiberweite harte Deny-CIDRs und unveränderbare Sperrklassen anwenden.
3. Ohne gebundenen EndpointGrant: ausschließlich Public-Profil.
4. Mit gültigem Grant: zusätzlich das konkrete Endpoint-Profil anwenden. Das Profil ersetzt Public-Zugriff durch eine genaue Endpoint-Bindung; es gibt nicht gleichzeitig beliebige öffentliche Ziele frei.
5. Jede aufgelöste Adresse muss innerhalb der für dieses Profil zulässigen Menge liegen.
6. Core-Allowlist und alle engeren Fachregeln bleiben zusätzlich wirksam.

Es gibt keine Regelreihenfolge nach dem Prinzip "erster Treffer gewinnt". Widersprüchliche Freigaben werden entweder durch Schnittmenge eingeschränkt oder bereits beim Laden als Fehler abgelehnt.

## 5. Adressklassen

Das Produkt spricht bewusst nicht nur von RFC1918. Ausgangsbasis sind versioniert mitgelieferte IANA-Spezialregister plus explizite Sicherheitsregeln. Registerdaten werden bei Entwicklung/Release aktualisiert, nie pro Webrequest aus dem Internet bezogen. Datum, Quelle und Hash des Datensatzes gehören zum Release. [S10-S11]

| Klasse | Public-Profil | Endpoint-Freigabe in v1 |
|---|---|---|
| Normale globale IPv4-/IPv6-Unicast-Adresse außerhalb ausgeschlossener Spezialbereiche | Erlaubt | Nur innerhalb der expliziten Endpoint-IP-Menge |
| RFC1918, IPv6 ULA, CGNAT | Gesperrt | Exakte Origin plus begrenzte CIDRs möglich |
| Loopback IPv4/IPv6 | Gesperrt | Nur explizites `allowLoopback` und /32 bzw. /128 |
| Link-Local, bekannte Cloud-Metadaten | Gesperrt | Keine Freigabe im generischen v1-Produkt |
| Unspecified, Multicast, Broadcast, reservierte/ungültige Adressen | Gesperrt | Keine |
| Dokumentations-, Benchmark-, Discard- und sonstige Spezialbereiche | Gesperrt | Keine produktive Freigabe in v1 |
| IPv4-mapped IPv6 | Eingebettete IPv4 prüfen und gleich entscheiden | Gleiche Regel; keine zweite, schwächere IPv6-Policy |
| NAT64-WKP, NAT64-Local-Use, 6to4, Teredo, deprecated IPv4-compatible | Gesperrt | Keine in v1 |
| Zusätzliche Betreiber-Deny-CIDRs | Gesperrt | Keine; Deny hat Vorrang |

Das Public-Profil ist konservativer als "IANA Globally Reachable = true": Spezialzwecknetze sind nicht automatisch sinnvolle HTTP-Ziele. Einzelfreigaben für neue Spezialdienste verlangen eine ADR-Änderung. IPv6 ist im Public-Profil auf reguläre globale Unicast-Zuteilungen begrenzt; eine beliebige nicht gelistete IPv6-Adresse wird nicht durch Negation einer kleinen Blacklist öffentlich.

Beispiele des Pflichtkorpus: `0.0.0.0/8`, `10.0.0.0/8`, `100.64.0.0/10`, `127.0.0.0/8`, `169.254.0.0/16`, `172.16.0.0/12`, `192.0.0.0/24`, `192.168.0.0/16`, `192.0.2.0/24`, `198.18.0.0/15`, `198.51.100.0/24`, `203.0.113.0/24`, `224.0.0.0/4`, `240.0.0.0/4`; `::`, `::1`, `fc00::/7`, `fe80::/10`, `ff00::/8`, `2001:db8::/32`, `3fff::/20`. Das sind Beispiele, kein Ersatz für den vollständigen versionierten Datensatz.

### 5.1 Zusätzliche harte Cloud-/Plattformziele

Zum initialen, versionierten Hard-Deny-Korpus gehören zusätzlich zu den Spezialnetzen:

| Adresse | Einordnung / Quelle |
|---|---|
| `169.254.169.254/32` | AWS IMDS; ohnehin durch das gesamte Link-Local-Netz gesperrt [S21] |
| `fd00:ec2::254/128` | AWS IMDS über IPv6; ein allgemeines ULA-Endpointprofil darf diese Adresse nicht freigeben [S21] |
| `100.100.100.200/32` | Alibaba ECS-Metadatendienst; Vorrang auch vor einer expliziten CGNAT-Freigabe [S22] |
| `168.63.129.16/32` | Azure-interne Plattform-/WireServeradresse in nominell öffentlichem IPv4-Raum [S23] |

Das sind explizite HTTP-Zielverbote des Anwendungsclients. Insbesondere bei Azure ist dies **keine** Empfehlung, diese Plattformadresse systemweit zu sperren: VM-Agent-, DNS- und Plattformverkehr außerhalb dieses Clients bleiben eine andere Betriebsaufgabe. Der Guard ändert keine Hostfirewall.

Harte Einträge werden vor Endpoint-CIDRs geprüft und gelten auch nach Auflösung eines beliebigen Alias sowie für IPv4-mapped-Darstellungen. Der Korpus ist kein Versprechen, sämtliche Cloudanbieterendpunkte vollständig zu kennen. Weitere Betreiber-/Providerziele werden als reviewte Datenupdates ergänzt. T017/T019/T021 prüfen diese Vorrangregel und die Ausnahme eines nominell öffentlichen Plattformziels.

## 6. Normalisierung

Die Bibliothek nimmt einen PSR-7-Request entgegen. Ein vorgelagerter Parser hat bereits syntaktische Arbeit geleistet; die Sicherheitsprüfung darf dessen Interpretation aber nicht blind mit der von cURL gleichsetzen.

Hostnamen werden ASCII-kleingeschrieben; genau ein abschließender FQDN-Punkt wird entfernt. Mehrfache Endpunkte, leere Labels, Steuerzeichen, Whitespace, Backslashes und Prozentkodierung in der Hostkomponente werden abgelehnt. Unicode-Hosts werden in v1 abgelehnt; vorab korrekt erzeugte ASCII-A-Labels sind erlaubt. Diese Entscheidung vermeidet unterschiedliche IDNA-Implementierungen im Guard und Transport.

IPv4 muss kanonisches dotted decimal ohne Oktal-/Hex-/Integer-/Kurzformen sein. IPv6 wird mittels binärer Parser normalisiert; URI-Klammern werden nur strukturell entfernt. Zone-IDs bleiben verboten. `inet_pton`/binäre CIDR-Tests sind maßgeblich, nicht Stringpräfixe.

Der nach der Normalisierung tatsächlich gesendete Request MUSS dieselbe kanonische Authority verwenden wie der Pin. TLS-Hostname, SNI und HTTP-Host bleiben der Zielhostname, nicht die gepinnte IP. Ein Fragment wird nicht still ignoriert, sondern abgelehnt; Userinfo muss in eine bewusste Authentifizierungskonfiguration überführt werden.

## 7. DNS, Hosts-Dateien und lokale Namen

Version 1 hat zwei kontrollierte Quellen: explizite statische Host-IP-Zuordnung aus deployter Konfiguration und einen DNS-Resolver für A/AAAA/CNAME. Statische Zuordnung ersetzt DNS für genau diesen Host; sie ist keine Freigabe. Auch statische Adressen werden vollständig geprüft und gepinnt.

Fehlende DNS-Antwort führt niemals zu einem unkontrollierten `getaddrinfo`-/NSS-/mDNS-Fallback. Ein nur in `/etc/hosts` bekannter Dienst wird in v1 über eine ausdrückliche statische Zuordnung integriert. Ein späterer NSS-Adapter muss selbst Adressen liefern, nicht bloß einen "vertrauenswürdigen Host" durchwinken. Diese Regel ist die bewusste strengere Weiterentwicklung von Vault ADR-038. [S06-S07]

Es werden nur Antworten für den gesuchten Namen bzw. seine nachvollzogene CNAME-Kette akzeptiert. Maximal acht Alias-Schritte, keine Zyklen, höchstens 64 verwertbare Adressen. Ungültige Daten erzeugen keinen Pin. Die DNS-Abfrage geschieht gegen den exakten FQDN, nicht über eine unkontrollierte Search-Domain-Erweiterung.

## 8. Credential-Grenze

Ein erlaubtes Netzwerkziel ist nicht automatisch ein erlaubter Empfänger für Secrets. nr-vault und sonstige credentialtragende Clients müssen Authentifizierungsregeln an die Origin binden. Ein authentifizierter POST wird niemals aufgrund einer Redirect-Antwort an eine andere Origin wiederholt. Payload und beliebige Custom-Header können Secrets enthalten; der Guard kann sie nicht zuverlässig erkennen.

Der globale Standardclient beschränkt deshalb Redirects auf dieselbe Origin. Ein gesonderter Public-Fetch-Client darf nach expliziter Wahl GET/HEAD ohne Body, Cookies, Authentifizierung und benutzerdefinierte Header originübergreifend weiterleiten. Er konstruiert pro Hop einen neuen Request aus einem sehr kleinen Header-Allowset. Vault verwendet diesen Client nicht.

## 9. Verbleibende Risiken

Ein Unternehmen kann öffentliche IP-Adressen intern routen oder einen internen Dienst unter einer öffentlichen Adresse betreiben. Das erkennt keine generische RFC1918-Prüfung. Betreiber müssen eigene Netze über Deny-CIDRs ergänzen und Egress-Regeln anwenden. Beliebige NAT64-Präfixe, NAT, transparente Proxies, Service-Mesh-Routing und Netzwerkmanipulation liegen außerhalb der Adressklassifikation.

Ein erlaubter Zielserver kann selbst als Relay agieren. Auch das ist nicht durch DNS-Pinning zu verhindern. DNS-Abfragen selbst können Informationen preisgeben; Eingabegrößen, Ratenbegrenzung und Resolver-Egress bleiben erforderlich. Ein synchron blockierender OS-/PHP-DNS-Aufruf lässt sich nicht nachträglich durch einen PHP-Zeitvergleich hart unterbrechen. Diese Verfügbarkeitsgrenze muss in Betriebsanforderungen und Messungen sichtbar bleiben.


---

<a id="doc-specs-03-architecture"></a>

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


---

<a id="doc-specs-04-configuration-and-api"></a>

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


---

<a id="doc-specs-05-nr-vault-migration"></a>

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


---

<a id="doc-specs-06-verification"></a>

# 06 - Teststrategie und Abnahme

Stand: 2026-10-08. Status: vorgeschlagener Testplan. Die nachfolgenden Tests wurden in diesem Auftrag nicht implementiert oder ausgeführt.

## 1. Testebenen

**Unit:** Normalisierung, binäre CIDR-Klassifikation, Policykomposition, Optionsanitizer, CNAME-/TTL-Regeln, Fehlercodes, Freigabelogik. Property-/Fuzztests erzeugen ungültige und mehrdeutige URI-/IP-Formen. Ein gemeinsamer Korpus ist für alle Adapter normativ.

**Integration:** Echter TYPO3-Bootstrap, DI, Core-Allowlist, Middlewarepositionen, Request-/Response-Reihenfolge, Guzzle-7/8-Adapter und Vault-Aufrufpfade. Ein Guzzle-Mockhandler allein beweist keine cURL-Pins.

**Wire/Sicherheit:** Lokale hermetische Testnetze mit kontrolliertem DNS, mehreren HTTP-/TLS-Zielen und Verbindungsmitschnitt. Ein als öffentlich klassifizierter Testhost wird nur innerhalb eines isolierten Netzwerk-Namespace geroutet; keine Tests gegen reale fremde Webseiten oder echte Cloud-Metadaten. Testprivilegien dürfen nicht als Produktions-`allowAll`-Flag existieren.

Für verbotene Ziele werden mindestens drei Signale erfasst: Transportspy, TCP-Verbindungszähler/SYN-Mitschnitt und HTTP-Requestzähler. Die Erwartung lautet **kein Zielkontakt**; DNS-Traffic zum ausdrücklich verwendeten Testresolver wird getrennt behandelt. Bei einem verbotenen Redirect darf der erste erlaubte Hop erfolgt sein, der verbotene Folgeserver muss unberührt bleiben.

**Last/Lebensdauer:** Gleichzeitige Requests, gleiche Host-Port-Kombination mit verschiedenen Policies, langlebiger Worker, Cancellation und Fehlerpfade. Garbage Collection allein ist kein Abbruchvertrag; Socketende wird am Server beobachtet.

## 2. Matrix

P0 = blockiert jede Produktionsfreigabe. P1 = Abnahme von Betrieb, Performance und Kompatibilität; Abweichungen benötigen eine dokumentierte Freigabe und dürfen keine Sicherheitsinvariante betreffen.

| Test | Priorität | Anforderungen | Szenario | Erwartung / Nachweis |
|---|---|---|---|---|
| T001 | P0 | HG-001, HG-005 | Registrierung in echten TYPO3-13.4-/14.3-Instanzen | Standard-RequestFactory ruft Boundary und Terminal genau einmal pro Versuch auf. |
| T002 | P0 | HG-002, HG-019 | Eigene Middleware vor/nach Core-Allowlist | Boundary ist erster, Terminal letzter eigener Eintrag; Core-Allowlist bleibt wirksam. |
| T003 | P0 | HG-002, HG-042 | Middleware nach Terminal / doppelte Registrierung / HandlerStack-Objekt | Konfigurationsfehler vor Zielkontakt; nichts still übersprungen. |
| T004 | P0 | HG-002, HG-009, HG-019 | Middleware schreibt Origin nach Core-Prüfung um | Authority-/Originfehler vor Versand; Core-Regel kann nicht umgangen werden. |
| T005 | P0 | HG-003, HG-004 | Leere neue Konfiguration | Enforce aktiv, keine internen Ausnahmen. |
| T006 | P0 | HG-003, HG-042 | Falscher Modus / unbekannte Konfigurationsfelder | Fehler, niemals implizites disabled oder allow. |
| T007 | P1 | HG-006 | Bibliothek ohne TYPO3 und nr-vault installieren | Komponenten und Tests funktionieren ohne Framework-Globals. |
| T008 | P0 | HG-007 | Kanonischer öffentlicher Host / Großschreibung / ein Endpunkt | Einheitliche Authority und identischer Pin; zulässiger hermetischer Versand. |
| T009 | P0 | HG-008 | file, gopher, ftp, dict, relative URL, Userinfo, Fragment | Ablehnung ohne Zielverbindung. |
| T010 | P0 | HG-007, HG-008 | Steuerzeichen, Backslash, Prozentkodierung im Host, leere Labels | Ablehnung vor DNS/Transport. |
| T011 | P0 | HG-008 | Integer-, Hex-, Oktal- und Kurz-IPv4 | Nichtkanonische Form wird abgelehnt; auch keine Allowlistumgehung. |
| T012 | P0 | HG-008 | IPv6-Zone-ID und ungültige URI-Klammern | Ablehnung ohne Transport. |
| T013 | P0 | HG-009 | Abweichender Host-Header oder Port | Kein Zielkontakt; identische normalisierte Authority darf passieren. |
| T014 | P0 | HG-010, HG-011 | IPv4 RFC1918 und Loopback direkt | Alle Grenzwerte gesperrt; Transportspy und Zielcounter bleiben null. |
| T015 | P0 | HG-010, HG-011 | IPv6 ULA, Loopback, Link-Local | Gesperrt einschließlich komprimierter Schreibweisen. |
| T016 | P0 | HG-010 | IPv4-mapped IPv6 mit interner und öffentlicher eingebetteter IPv4 | Gleiche Policy wie für die eingebettete Adresse, kein Familienbypass. |
| T017 | P0 | HG-011 | Cloud-Metadaten IPv4/IPv6, Unspecified, Multicast | Keine generische Endpointausnahme möglich. |
| T018 | P0 | HG-011 | CGNAT, Dokumentation, Benchmark, Reserved | Konservative Sperrklassen vollständig getestet. |
| T019 | P0 | HG-011 | NAT64, 6to4, Teredo, deprecated compatible | v1 blockiert statt Tunnelpfad falsch als public zu klassifizieren. |
| T020 | P0 | HG-010, HG-011 | CIDR-Grenzen aller mitgelieferten Bereiche | Erste/letzte Adresse sowie angrenzende Kandidaten gemäß Korpus. |
| T021 | P0 | HG-012, HG-019 | Betreiber-Deny überlappt Endpoint-Freigabe | Deny gewinnt, auch bei gültigem Grant. |
| T022 | P0 | HG-013 | DNS liefert public A und private AAAA | Gesamter Versuch blockiert, kein safe-only Wegfiltern. |
| T023 | P0 | HG-013 | DNS liefert public AAAA und private A | Gleicher Ausgang, unabhängig von Familienpräferenz. |
| T024 | P0 | HG-014 | NXDOMAIN, SERVFAIL, leere Antwort, ungültige Recorddaten | Kein Transportaufruf; kein ungeprüfter Systemresolverfallback. |
| T025 | P0 | HG-014, HG-017 | Freigegebener privater Host ohne verwertbare Auflösung | Trotz Grant ablehnen. |
| T026 | P0 | HG-014, HG-015 | Nur hosts/NSS bekannter Name, keine statische Zuordnung | Kein unkontrollierter Durchgriff auf den Transportresolver. |
| T027 | P0 | HG-014, HG-015, HG-017 | Statischer Host mit enger interner Freigabe | Exakt gepinnter interner Testendpoint erreichbar; URL-Importer bleibt gesperrt. |
| T028 | P0 | HG-013, HG-044 | CNAME zu privat, Schleife, mehr als acht Hops, mehr als 64 Adressen | Ablehnung; keine still abgeschnittene oder unvollständig geprüfte Antwort. |
| T029 | P0 | HG-015 | Resolver liefert zuerst public, anschließend private | Wire-Test zeigt ausschließlich gepinnte public Testadresse; kein zweiter Zielresolver. |
| T030 | P0 | HG-015, HG-016 | Pin auf nicht erreichbare Adresse | Verbindungsfehler ohne Fallback auf ungeprüftes DNS. |
| T031 | P0 | HG-016 | Mehrere A/AAAA, erste Adresse unerreichbar | Fallback nur innerhalb geprüfter Menge; ein Multi-Address-Pin bleibt vollständig. |
| T032 | P0 | HG-016 | Fehlerhafte Pinzeichenfolge / Optionsetzer schlägt fehl | Vor Zielkontakt fehlschlagen, keine Option ignorieren. |
| T033 | P0 | HG-015 | TLS-SNI, Zertifikatsname und Host bei Pinning | Originhost bleibt erhalten; interne IP wird nicht zum virtuellen Host. |
| T034 | P0 | HG-017, HG-018 | ERP-Grant gegen andere Origin, Port, Methode oder CIDR | Jede Abweichung blockiert. |
| T035 | P0 | HG-017, HG-018 | Benutzer setzt Profilstring, Header oder gefälschtes Grantobjekt | Keine interne Freigabe. |
| T036 | P0 | HG-017, HG-018 | Public-Importer ruft konfigurierten ERP-Host auf | Blockiert; Konfiguration allein erweitert Public-Profil nicht. |
| T037 | P0 | HG-017 | Loopback mit /32 bzw. /128 und explizitem Flag | Nur korrekt gebundener Client erlaubt; Flag oder Präfix allein reichen nicht. |
| T038 | P0 | HG-017, HG-043 | Ablauf eines Grants, geänderte Policyrevision, verzögert eingeplanter Versuch | Alter Grant/Plan ungültig; neue Requests nutzen neue Regeln. |
| T039 | P0 | HG-019, HG-038 | Core-Kontextallowlist plus neue Guardpolicy | Beide müssen erlauben; flache Vault-Liste wird nicht übernommen. |
| T040 | P0 | HG-020 | Caller liefert RESOLVE, CONNECT_TO, URL, SHARE, Unix-Socket, FOLLOWLOCATION | Alle verbotenen Eingriffe abweisen, bevor der Transfer beginnt. |
| T041 | P0 | HG-020, HG-005 | Guzzle-7/8-Optionen, verbotener delay und Promise-/PSR-7-Majors | Belegte kompatible Kombinationen verhalten sich gleich; unknown options scheitern sichtbar. |
| T042 | P0 | HG-021 | Kein ext-curl, kein curl-multi, inkompatible Version | Enforce verweigert statt Streams zu verwenden. |
| T043 | P0 | HG-022 | HTTP-/HTTPS-/SOCKS-Proxy explizit | Kein Kontakt zu Proxy oder Ziel; kein stiller Direct-Bypass. |
| T044 | P0 | HG-022 | Proxy über echte Umgebung inklusive Case-/NO_PROXY-Varianten; eingehender Proxy-Header separat | Echte/mehrdeutige Proxykonfiguration ablehnen; CGI-Header nicht als vertrauenswürdige Prozessumgebung behandeln. |
| T045 | P0 | HG-023 | Redirect auf private IP und auf private DNS-Adresse | Erster erlaubter Hop möglich, verbotener Folgehop ohne Zielkontakt. |
| T046 | P0 | HG-023, HG-024 | Same-Origin-Redirect und Cross-Origin-Redirect | Same-Origin erneut prüfen; Cross-Origin im Standardclient verweigern. |
| T047 | P0 | HG-024 | 307/308 mit Body-Secret oder Custom-Auth-Header zu anderer Origin | Kein Versand an zweite Origin; keine Secret-Erkennung als Voraussetzung. |
| T048 | P0 | HG-023, HG-024 | Response-Middleware verändert Location nach Terminal | Boundary prüft finalen Response und verhindert verbotenen Folgehop. |
| T049 | P0 | HG-023 | Relative/Schema-relative Location, Downgrade, Schleife, Requestlimit oberhalb/unterhalb Betreibergrenze | Korrekte URI-Auflösung; zu großes Followlimit vor erstem Versand ablehnen, kleineres erhalten; kein unwirksames inneres Clamp. |
| T050 | P0 | HG-024 | Public-Fetch über mehrere öffentliche Origins | Nur GET/HEAD ohne Body/Cookie/Secrets; kleiner fester Headersatz. |
| T051 | P0 | HG-023 | allow_redirects=false / PSR-18-Send | 30x als Response zurückgeben; kein versehentlicher Follow. |
| T052 | P0 | HG-025 | Retrymiddleware wiederholt Request nach Netzwerkfehler | Jeder Versuch neuer Plan; keine automatische Wiederholung von Policyablehnungen. |
| T053 | P0 | HG-026, HG-040 | Parallel gleicher Host/Port mit unterschiedlicher Policy und IP | Keine DNS-/Connection-/Grant-Kontamination; Zielcounter getrennt. |
| T054 | P0 | HG-026, HG-040 | Langlebiger Worker, erst interne Freigabe dann public Request | Keine wiederverwendete privilegierte Verbindung oder Allow-Entscheidung. |
| T055 | P0 | HG-026 | Externes/persistentes Transport-Sharing | Ablehnung; interne Handler bleiben isoliert. |
| T056 | P0 | HG-027 | Memo-Hit bei geänderter Policy | Adressen erneut bewerten; Cache ist keine Freigabe. |
| T057 | P1 | HG-027, HG-044 | TTL 0, Ablauf, Kapazität, negative Antwort | Kein unbeschränkter Speicher; negative/TTL0-Antwort nicht positiv cachen. |
| T058 | P0 | HG-028 | stream=true trotz installiertem cURL | Explizite Ablehnung im globalen Enforcepfad, kein StreamHandler. |
| T059 | P0 | HG-029, HG-037 | Vault-sendStreaming mit gültigem Pin | Erste Bytes vor Transferende, keine stream-Option, identischer Credentialpfad. |
| T060 | P0 | HG-029, HG-030, HG-037 | Cancellation vor Send und während Transfer | Keine neue Verbindung bzw. aktiver Socket geschlossen; Audit konsistent. |
| T061 | P0 | HG-030 | Body-close, teilweiser Read, Transferfehler, Exception im Callback | Keine Hintergrundverbindung/Lease-Leaks; unvollständige Antwort nicht als Erfolg. |
| T062 | P0 | HG-031 | Synchrone und asynchrone Policyfehler, PSR-18 | Stabile Reason-Codes, passende Exceptioninterfaces, abgelehnte Promise. |
| T063 | P0 | HG-032 | Secrets in Query, Body, Headers, Zertifikatspfaden und Exception | Kein Secret in Guardlogs oder Metriklabels. |
| T064 | P1 | HG-032 | Loggerausfall und Denial-Flood | Ablehnung bleibt Ablehnung; Rate-Limit und Zähler funktionieren. |
| T065 | P0 | HG-033 | Observe mit verbotenem Ziel / unsupported Transport | would_deny bzw. unverifiable, Status nicht protected; Verhalten bleibt explizit ungeschützt. |
| T066 | P1 | HG-033, HG-043 | Disabled und bewusster Rollback | Status sichtbar reduziert; kein automatischer Moduswechsel. |
| T067 | P1 | HG-034 | Doctor bei normalem/fehlerhaftem Stack | Korrekte Capabilities, Modus und Grenzen; kein Probe-HTTP. |
| T068 | P1 | HG-035 | Policy-check mit und ohne Endpoint | DNS optional, kein HTTP; Ergebnis kein wiederverwendbares Senderecht. |
| T069 | P0 | HG-036 | Nur globale Extension installiert, Vault unverändert | Diagnose behauptet keine Vault-Abdeckung. |
| T070 | P0 | HG-036, HG-037 | Vault mit bewusst integriertem Adapter | Normal, Token, Cancellation und Streaming nutzen gemeinsame Policy. |
| T071 | P0 | HG-037 | OAuth-Tokenendpoint intern, Resourceendpoint extern und umgekehrt | Beide Requests getrennt autorisiert/gepinnt; kein Grantübertrag. |
| T072 | P0 | HG-037 | Vault-Authentifizierungsarten und redigiertes Audit | Identische Fachfunktion ohne Credentialexport oder Logging. |
| T073 | P0 | HG-038, HG-043 | Legacyreport zu flachen/verschachtelten allowed_hosts | Keine aktiven Regeln automatisch erzeugt; fehlende Angaben sichtbar. |
| T074 | P0 | HG-039, HG-041 | Mutation: Prüfung deaktiviert oder Pin entfernt | Kritischer Test wird rot; Spy plus Wire-Nachweis statt nur Exceptions. |
| T075 | P0 | HG-040 | Parallelität mit wechselnden DNS-Antworten und Grants | Verbindung jedes Transfers bleibt in dessen eigener geprüfter Menge. |
| T076 | P1 | HG-041, HG-045 | Gemeinsamer Regressionstest in Library, TYPO3 und Vault | Gleicher Sicherheitsfall in allen integrierten Pfaden nachweisbar. |
| T077 | P0 | HG-042 | Fremder SDK, direkter cURL, requesteigener Handler | Als außerhalb Scope dokumentiert; keine falsche globale Garantie. |
| T078 | P1 | HG-044 | DNS-Blackhole / langer Resolvercall | Gemessene Grenze dokumentiert; kein behaupteter unterbrechbarer Timeout ohne Implementierung. |
| T079 | P1 | HG-044 | Klassifikation mit 64 Adressen/128 Profilen, große Responses | p95-Ziel auf Referenzsystem; Body nicht durch Guard dupliziert. |
| T080 | P0 | HG-043, HG-045 | Abhängigkeitsupdate ändert Handler/Raw-Optionen | Gesamter P0-Korpus läuft; keine Freigabe durch Composer-Auflösung allein. |
| T081 | P0 | HG-023, HG-017 | POST->GET Redirect bei eingeschränktem Methodenprofil | Neue Methode erneut prüfen; keine still erweiterte Methodenfreigabe. |
| T082 | P1 | HG-005, HG-044 | TLS mit eigener CA, mTLS, verify=false nach Policy | Konfiguration erhalten bzw. explizit verweigert, niemals still verändert. |
| T083 | P0 | HG-002, HG-019 | Reihenfolge Request-/Response-Middlewares | Jede bestehende Middleware genau wie vereinbart ausgeführt; keine verdeckt übersprungen. |
| T084 | P0 | HG-015, HG-026 | cURL-Alt-Svc, HSTS, automatische Protokoll-/Routenwechsel | Kein aktivierbarer ungeprüfter Alternativpfad im isolierten Adapter. |

## 3. Vollständige Anforderungszuordnung

| Anforderung | Mindestens zugeordnete Tests |
|---|---|
| HG-001 | T001 |
| HG-002 | T002, T003, T004, T083 |
| HG-003 | T005, T006 |
| HG-004 | T005 |
| HG-005 | T001, T041, T082 |
| HG-006 | T007 |
| HG-007 | T008, T010 |
| HG-008 | T009, T010, T011, T012 |
| HG-009 | T004, T013 |
| HG-010 | T014, T015, T016, T020 |
| HG-011 | T014, T015, T017, T018, T019, T020 |
| HG-012 | T021 |
| HG-013 | T022, T023, T028 |
| HG-014 | T024, T025, T026, T027 |
| HG-015 | T026, T027, T029, T030, T033, T084 |
| HG-016 | T030, T031, T032 |
| HG-017 | T025, T027, T034, T035, T036, T037, T038, T081 |
| HG-018 | T034, T035, T036 |
| HG-019 | T002, T004, T021, T039, T083 |
| HG-020 | T040, T041 |
| HG-021 | T042 |
| HG-022 | T043, T044 |
| HG-023 | T045, T046, T048, T049, T051, T081 |
| HG-024 | T046, T047, T048, T050 |
| HG-025 | T052 |
| HG-026 | T053, T054, T055, T084 |
| HG-027 | T056, T057 |
| HG-028 | T058 |
| HG-029 | T059, T060 |
| HG-030 | T060, T061 |
| HG-031 | T062 |
| HG-032 | T063, T064 |
| HG-033 | T065, T066 |
| HG-034 | T067 |
| HG-035 | T068 |
| HG-036 | T069, T070 |
| HG-037 | T059, T060, T070, T071, T072 |
| HG-038 | T039, T073 |
| HG-039 | T074 |
| HG-040 | T053, T054, T075 |
| HG-041 | T074, T076 |
| HG-042 | T003, T006, T077 |
| HG-043 | T038, T066, T073, T080 |
| HG-044 | T028, T057, T078, T079, T082 |
| HG-045 | T076, T080 |

## 4. Release-Gates

**G0 - Architekturprobe:** Tatsächlicher Bootstrap und Zwei-Middleware-/Terminalintegration funktionieren in beiden TYPO3-Linien. Eigene Handlerlebensdauer und Guzzle-7/8-Optionen sind belegt. Fehlender Nachweis stoppt die Umsetzung des gewählten Integrationswegs.

**G1 - Sicherheitskorpus:** Alle P0-Tests grün; kein ignorierter/skippter P0-Test in einer als unterstützt deklarierten Kombination. Kein bloßer "Exception erwartet"-Ersatz für Wire-Nachweise.

**G2 - Mutationsnachweis:** Entfernte Adressprüfung, entferntes Pinning, erlaubter Resolve-Fallback, versehentliches Proxy-/Stream-Passthrough und deaktivierte Grantbindung müssen jeweils mindestens einen kritischen Test scheitern lassen. Ein hoher globaler Coveragewert ersetzt diese gezielten Mutationen nicht.

**G3 - Integration:** Core-Allowlist und alle bestehenden Middlewareaufrufe erhalten; Vault normal/OAuth/streaming/cancellable separat grün. Wenn der Vault-Adapter noch nicht ausgeliefert wird, darf nur die eigenständige Extension als fertig gelten.

**G4 - Betriebsfähigkeit:** Diagnose, redigierte Logs, explizite Ausnahmen, Rollback und Upgradehinweise getestet. Proxy- und Streameinschränkungen stehen im Installationsleitfaden, nicht nur in Quellcodekommentaren.

**G5 - Abhängigkeiten:** Gesperrte/unbelegte Guzzle-/Promise-/PSR-7-/libcurl-Kombinationen ausgeschlossen; Composer-Audit und Betriebssystem-Vulnerabilityprüfung dokumentiert. Distributionsbackports nachvollziehbar bewerten, nicht allein Versionsstrings vergleichen.

**G6 - unabhängige Prüfung:** Securityreview durch eine andere Person als die Erstimplementierung. Geprüft werden insbesondere Parserdifferenzen, Rohoptionen, Reihenfolge, DNS-/Poolzustand und alle bewusst nicht unterstützten Pfade. Offene hohe Risiken werden nicht als allgemeine Warnung wegdokumentiert.

## 5. Evidenzformat

Jeder Testlauf speichert Commit, Dependency-Lock/aufgelöste Versionen, PHP-Version, OS-/libcurl-Build, Konfigurationsrevision, Testkorpusrevision und Ergebnisse. Wire-Tests speichern nur synthetische Testdaten; keine Produktiv-Credentials oder Produktivziele. Fehlgeschlagene Fuzzseeds werden reproduzierbare Regressionen.

## 6. Definition of Done

Vollständige Anforderungen-Tests-Zuordnung; alle Sicherheitsinvarianten nachgewiesen; kein automatischer offener Fallback; keine ungetestete Behauptung "schützt alle HTTP-Requests"; dokumentierte Performancekosten; getesteter Integrations- und Migrationspfad; unabhängiges Review. Die Implementierungsfreigabe dieses Entwurfs ist nicht identisch mit dieser Produktionsfreigabe.


---

<a id="doc-specs-07-delivery-and-operations"></a>

# 07 - Umsetzung, Betrieb und Einführung

Stand: 2026-10-08. Status: vorgeschlagen. Arbeitspakete sind umsetzbar beschrieben, aber nicht als angelegte Tickets oder zugesagte Termine zu verstehen.

## 1. Arbeitspakete und Reihenfolge

| Paket | Ergebnis | Abhängigkeiten | Fertig, wenn |
|---|---|---|---|
| AP-01 Integrations- und Transportprobe | Minimaler echter TYPO3-Stack mit Boundary/Terminal, isoliertem cURL und Fehlerpfaden | Freigabe dieses Entwurfs | G0 nachgewiesen; 13.4/14.3, Guzzle 7/8, Registryposition und Response-Reihenfolge getestet |
| AP-02 Threat Model und Sicherheitskorpus | Versionierte Adressdaten, URI-/IP-/DNS-/Grantfälle | AP-01 parallel möglich | INV-01 bis INV-08 mit Tests verknüpft |
| AP-03 Gemeinsame Policybibliothek | Normalisierung, Klassifikation, Resolver, Registry, Entscheidungen | AP-02 | Unit-/Fuzztests und harte Negativfälle grün |
| AP-04 Kontrollierter Transport | ConnectionPlan, Multi-Address-Pin, Lease, Optionsanitizer | AP-01, AP-03 | Wire-, Pool-/Parallelitäts- und Cancellation-Nachweise |
| AP-05 TYPO3-Extension | Konfiguration, Registrierung, Diagnose, Public/Endpoint-Clients | AP-04 | Erfasst tatsächliche RequestFactory- und deklarierte PSR-18-Pfade |
| AP-06 Betrieb und Telemetrie | Redigierte Logs, CLI, Migrationsbericht, Runbook | AP-05 | Keine Secrets, nachvollziehbare Ablehnungen, Diagnose ohne Probe-HTTP |
| AP-07 Vault-Charakterisierung | Testinventar und gesicherte Ist-Verträge | Früh parallel | Send/OAuth/Streaming/Cancellation und Legacy-Ausnahmen dokumentiert |
| AP-08 Vault-Adapter | Gemeinsame Guardnutzung ohne Verlust der Fachfunktionen | AP-04, AP-07 | Vault-spezifische P0-Tests und Pilotmigration grün |
| AP-09 Unabhängiges Review | Prüfprotokoll und geschlossene Findings | AP-05 bis AP-08 | Keine offenen verletzten Sicherheitsinvarianten |
| AP-10 Pilot und Freigabe | Freigaben geprüft, Einschränkungen akzeptiert, Monitoring aktiv | AP-09 | Alle anwendbaren Gates aus Dokument 06 erfüllt |

AP-01 ist bewusst zuerst: Eine gute IP-Prüfung nützt nichts, wenn Middlewareposition, Proxyumgebung oder Sharing den falschen Transport nutzen. Scheitert diese Probe, wird ADR-0003 revidiert, bevor umfangreiche Fachimplementierung entsteht.

## 2. Reihenfolge der Produktlieferung

Erste nutzbare Lieferung: gemeinsame Bibliothek plus unabhängige TYPO3-Extension im beschriebenen v1-Umfang. Die volle Vereinheitlichung mit nr-vault ist erst nach AP-08 erreicht; eine fertige Extension ist keine Erlaubnis, Vaults bestehende Prüfungen vorher zu entfernen.

Nicht Bestandteil v1: PHP-Stream-Transport, HTTP-/SOCKS-Proxies, HTTP/3, automatische Endpointfreigaben, GUI-Policyeditor, automatische Erkennung aller fremden SDKs, Cross-Request-Pooling. Weiterentwicklung jeweils mit eigenem ADR und denselben Sicherheitsgates.

## 3. Deploymentvoraussetzungen

PHP/cURL-/curl-multi-Fähigkeiten, gewählte Guzzle-Version und effektive Proxykonfiguration prüfen. Unterstützte Composerkombinationen sperren, nicht nur in README behaupten. Aktualisierte Systempakete und Distributor-Sicherheitsstatus sind Betreiberpflicht. Die funktionale cURL-Untergrenze ist kein Freibrief für alte ungepatchte Builds.

Der Betrieb stellt endliche Systemresolver-Timeouts, Workerlimits und Egress-Regeln sicher. DNS darf nur zu autorisierten Resolvern gehen. Unnötige Wege zu Metadaten, privaten Managementnetzen und lokalen Adminports bleiben auf Netzwerkebene blockiert. Anwendungsschutz und Netzwerkfilter sind unabhängige Schichten.

Der Guard erwartet unveränderliche Konfigurationssnapshots. Änderungen werden über Deployment/Cache-Neuaufbau und bei langlebigen Workern durch kontrollierten Neustart aktiviert. Keine teilweise geänderte globale Policy während eines Transfers. `expiresAt` wird vor jedem neuen Versuch gegen eine injizierte Clock geprüft; bereits gestartete erlaubte Transfers werden nicht als sofort widerrufen dargestellt.

## 4. Einführung in Bestandsprojekte

1. Aufrufpfade inventarisieren: Standard-RequestFactory, PSR-18-Alias, Vault, SDKs, eigene Guzzleclients, direkte cURL-/Streamaufrufe. Unbekannte Wege bleiben als unbekannt markiert.
2. In Staging `config-check` und `doctor` ausführen. Falsche Reihenfolgen und Unsupported-Capabilities vor produktivem Traffic beheben.
3. Bei Bedarf ausdrücklich `observe` einsetzen. Der Zeitraum ist eine bewusste Phase ohne zusätzliche Blockgarantie; niemand darf das Dashboard als "geschützt" ausgeben.
4. Benötigte interne Integrationen einzeln fachlich begründen. Exakte Origin, Methoden und Adressen bestimmen und einen gebundenen Client verdrahten. Beobachteter Traffic wird nicht automatisch erlaubt.
5. Enforce zunächst im kontrollierten Pilot aktivieren, Funktions- und Denialraten prüfen. Danach gezielt ausrollen. Kein automatisch stilles Zurückfallen bei Fehlerquoten.
6. nr-vault separat gemäß Dokument 05 migrieren; Credentialpfade und Audit besonders prüfen.

Ein Zugriff, der vor der Einführung bereits durch eine andere Sicherheitskontrolle blockiert wurde, darf im Observe-Modus nicht dadurch geöffnet werden, dass diese alte Kontrolle entfernt wird. Beobachtung bedeutet Zusatzdiagnose, nicht Abschalten bisheriger Abwehr.

## 5. Runbook: Request wurde blockiert

Reason-Code und Profil-ID lesen; keine volle URL samt Secret in Tickets kopieren. Bei `address_forbidden` prüfen, ob ein interner Dienst wirklich zum Zweck dieses Clients gehört. Bei `resolution_unverified` DNS-/StaticHosts-Konfiguration kontrollieren, nicht mit allgemeiner Allowlist umgehen. Bei `authority_mismatch` eine URL-umschreibende Middleware oder Host-Header-Konfiguration korrigieren. Bei `proxy_unsupported` ist ein genehmigter Proxyadapter oder ein anderes Deployment nötig; den Unternehmensproxy nicht heimlich umgehen.

Neue Ausnahme nur nach Review deployen. Ein interner API-Fehler ist kein Anlass, ein gesamtes RFC1918-Netz zu öffnen. Wird bewusst zurückgerollt, Status auf reduzierte Sicherheitslage setzen und Entscheidung dokumentieren.

## 6. Runbook: Sicherheitsvorfall oder Umgehungsverdacht

Synthetisch reproduzierbaren Fall erstellen, betroffenen Transportpfad und Versionen erfassen, ungewollten Zielkontakt anhand redigierter Netzwerkevidenz prüfen. Bei bestätigtem Problem Guardpfad bzw. betreffende Funktion deaktivieren oder auf Netzwerkebene sperren; nicht zu einem schwächeren Fallback wechseln.

Gemeinsame Bibliothek und beide Integrationen auf denselben Fehler untersuchen. Fix erhält Regressionsfall und unabhängiges Review. Vertrauliche Meldungen laufen über den vorgesehenen Securitykontakt des Projekts; der Entwurf erfindet kein Bug-Bounty-Programm oder SLA.

## 7. Runbook: Performance

DNSzeit, Klassifikationszeit, TLS-/Connectzeit und Transferzeit getrennt messen. Ein hoher Handshakeanteil ist bei isolierten Transfers erwartbar. Erst Daten erheben, dann Pooling als neue Architekturentscheidung prüfen. Ein Performancefix darf weder Pins entfernen noch DNS-Memoisierung zu einer Allow-Cache-Entscheidung umwandeln.

Cachegrenzen und URI-/Policygrenzen sind technisch fest: maximal 8192 Bytes URI, 128 konfigurierte Endpointprofile, 64 aufgelöste Adressen und acht CNAME-Schritte in v1. Überlänge wird vor unnötiger Arbeit abgelehnt. Der Guard puffert Bodies nicht zusätzlich; Fachclients müssen selbst sinnvolle Antwortgrößen und Zeitbudgets setzen.

## 8. Vor Freigabe zu liefernde Evidenz, keine unentschiedenen Produktdefaults

| Gate | Benötigter Nachweis |
|---|---|
| Registrierungsreihenfolge | Echter Bootstrap in 13.4/14.3, auch nach allen Extension-/Projektkonfigurationen |
| Guzzle-Adapter | Tatsächlich erlaubte Raw-/High-Level-Optionen je unterstütztem Major |
| Isolation | Kein geteilter DNS-/Connectionzustand bei parallel gleichem Host |
| DNSbetrieb | Gemessene OS-/Resolverzeitgrenzen; ehrliche Dokumentation fehlender harter PHP-Abbruchgarantie |
| Vault-BC | Normal-/OAuth-/Streaming-/Cancellation-Regression und geprüfte neue Capabilityvoraussetzungen |
| Sicherheitsbaseline | Konkrete Versionslocks, Systempaketstatus und unabhängiges Review |

Die Produktentscheidungen sind in diesem Entwurf getroffen; diese Nachweise sind Implementierungsaufgaben. Scheitert ein Nachweis, wird die betreffende Entscheidung geändert und neu dokumentiert. Ein fehlender Test wird nicht durch eine optimistische README-Aussage ersetzt.


---

<a id="doc-specs-08-evidence-and-sources"></a>

# 08 - Evidenz, Quellen und Unsicherheiten

Abruf-/Prüfdatum: 8. Oktober 2026.

## 1. Untersuchte Stände

- nr-vault: `5a070c396a614e5b05f63d79fa564c3748cf21eb`, Commitdatum 2026-10-07; kein erfundener Releasebezug.
- TYPO3 Branch 14.3: aufgelöster Commit `cfa1b1cbf3022c19ba231731da397f5847f3c29a`; Factorydatei zusätzlich über ihren Blob identifiziert.
- Guzzle: beim gezielten Code-Search gefundener Stand `93939470950a9b11e2e84204166ef5e048c55fe4`. Die dort beschriebenen neuen APIs sind nicht ungeprüft jeder Guzzle-Installation zuzuordnen.
- TYPO3 13.4 ist ein verbindliches Implementierungs-/Testziel dieses Entwurfs, kein in diesem Dokument behaupteter vollständiger Branch-Audit.

## 2. Was geprüft wurde

Gezielte Quellcode- und ADR-Lektüre zur Architekturfrage. Core-Erweiterungspunkt, Kontextweitergabe, Vault-Handlerstack, Adress-/DNS-/Pinningregeln, Literal-Allowlist-Ausnahmen, degradierte Transportpfade, Streamingarchitektur und aktuelle Dependency-Majors wurden nachvollzogen. Daraus wurde ein eigenständiger Sollentwurf erstellt.

## 3. Was nicht nachgewiesen wurde

Keine neue Implementierung, kein vollständiger Vault-Sicherheitsaudit, keine automatisierte Repositoryanalyse, keine erneut ausgeführten Vault-Unit-/Wire-Tests, keine live ausgenutzte Schwachstelle. Eine Aussage aus einem bestehenden ADR ist nicht derselbe Nachweis wie ein hier ausgeführter Test.

Ein lokaler Repository-Clone und der dokumentierte Context7-CLI-Abruf waren in der Arbeitsumgebung mangels verfügbarer Netzauflösung nicht nutzbar. Die relevanten Dateien wurden über die GitHub-Verbindung gelesen; öffentliche technische Referenzen über Webabrufe geprüft. Daraus wird kein erfolgreicher Build oder Testlauf abgeleitet.

Die eigene Dokumentlieferung wird auf vorhandene Dateien, interne Verweise, eindeutige IDs und Anforderungs-Test-Zuordnung mechanisch geprüft. Das ist Dokument-QA, nicht Produkt-Sicherheitsverifikation.

## 4. Fakten und Entscheidungen auseinanderhalten

"nr-vault hat einen separaten Stack" ist ein Quellcodebefund. "Die neue Library soll isolierte Transfers benutzen" ist eine vorgeschlagene Architekturentscheidung. "Ein bestimmter Proxybetrieb ist verwundbar" wäre eine gesondert zu belegende Sicherheitsbehauptung und wird hier nicht pauschal erhoben.

Entscheidungen zum Umfang, zum strengen Ausnahmeformat, zu nicht unterstützten Proxies/Streams und zu isolierten Verbindungen sind bewusst konservativ. Sie sollten nur nach konkreten Testergebnissen geändert werden, nicht aus Gründen scheinbarer Transparenz oder geringeren Konfigurationsaufwands.

## 5. Quellenverzeichnis

### S01 - TYPO3 GuzzleClientFactory: Stackaufbau und Kontextallowlist

Quelle: https://github.com/TYPO3/typo3/blob/cfa1b1cbf3022c19ba231731da397f5847f3c29a/typo3/sysext/core/Classes/Http/Client/GuzzleClientFactory.php

Vollständige Datei gelesen; Blob 9a4ee2cb7c62d90cafeb9700604c02d7b8a3db65.

### S02 - Offizielle TYPO3-Dokumentation zum ausgehenden Middleware-Erweiterungspunkt

Quelle: https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/ExtensionArchitecture/HowTo/RestRequests/Index.html

Abschnitt Custom middleware handlers sowie RequestFactory-Verhalten gelesen.

### S03 - TYPO3 RequestFactory: request-Optionen und Kontext

Quelle: https://github.com/TYPO3/typo3/blob/cfa1b1cbf3022c19ba231731da397f5847f3c29a/typo3/sysext/core/Classes/Http/RequestFactory.php

Vollständige Datei gelesen; Kontext wird an getClient weitergegeben.

### S04 - nr-vault SecureHttpClientFactory: eigener Stack, Vorprüfung, Optionen

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Classes/Http/SecureHttpClientFactory.php

Gezielt create, createCancellable, isHostAllowed sowie Middleware und Pinaufbau gelesen; keine Behauptung einer vollständigen Repositoryprüfung.

### S05 - nr-vault SecureHttpClientFactory: Streamgrenze, Ausnahmen, Pinaufbau und Allowlist

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Classes/Http/SecureHttpClientFactory.php#L610-L960

Insbesondere Warnung ohne cURL, pinWillBeHonoured, buildResolveEntries und resolveAllowedHostsList geprüft.

### S06 - nr-vault ADR-038: ungeprüfte Auflösung wird abgelehnt

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Documentation/Developer/Adr/ADR-038-UnresolvableHostIsRefused.rst

Vollständig gelesen; beschreibt ausdrücklich die verbleibende Literal-Allowlist-Ausnahme.

### S07 - nr-vault DefaultDnsResolver

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Classes/Http/DefaultDnsResolver.php

Vollständig gelesen; dns_get_record A/AAAA und Leerantwortsemantik.

### S08 - nr-vault ADR-039: Streaming ohne Verlust des DNS-Pins

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Documentation/Developer/Adr/ADR-039-StreamingSendKeepsTheDnsPin.rst

Ausführliche Abschnitte zu Entscheidung, curl-multi, Headers, Puffern, Fehlern und Zeitgrenzen gelesen; vorhandene Messungen nicht erneut durchgeführt.

### S09 - libcurl: CURLOPT_RESOLVE

Quelle: https://curl.se/libcurl/c/CURLOPT_RESOLVE.html

Offizielle Referenz zum Host-Port-Pin und Multi-Address-Format; funktionale Untergrenzen sind keine Securitybaseline.

### S10 - IANA IPv4 Special-Purpose Address Space

Quelle: https://www.iana.org/assignments/iana-ipv4-special-registry/iana-ipv4-special-registry.xhtml

Register und Bedeutung Globally Reachable gelesen. Angezeigter Registerstand 2025-10-09; bei Implementierung erneut versionieren.

### S11 - IANA IPv6 Special-Purpose Address Space

Quelle: https://www.iana.org/assignments/iana-ipv6-special-registry/iana-ipv6-special-registry.xhtml

Register inklusive IPv4-mapped, NAT64, Documentation und ULA gelesen. Angezeigter Stand 2025-10-09.

### S12 - Guzzle: Connection reuse and sharing

Quelle: https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/docs/contributing/curl-connection-reuse.md

Insbesondere Pooling, raw-option gating, Sharing und versionsabhängige Grenzen gelesen. Entwicklungsbranchstand, keine pauschale Aussage über alle Releases.

### S13 - TYPO3 Core composer.json

Quelle: https://github.com/TYPO3/typo3/blob/cfa1b1cbf3022c19ba231731da397f5847f3c29a/typo3/sysext/core/composer.json

require-Abschnitt gelesen; PHP ^8.2, Guzzle ^7.15.2 oder ^8.0 im untersuchten 14.3-Stand.

### S14 - Guzzle CurlMultiHandler

Quelle: https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/src/Handler/CurlMultiHandler.php

Gezielter Anfangsausschnitt mit Handler-/Sharing-/Pool-Lebensdauer gelesen; nicht alle Interna dieses großen Handlers auditiert.

### S15 - Guzzle request options

Quelle: https://docs.guzzlephp.org/en/stable/request-options.html

Primärreferenz für Optionen und on_stats; normative neue Optionen dieses Entwurfs sind davon getrennt.

### S16 - nr-vault composer.json

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/composer.json

Abhängigkeiten, Lizenz und optionales ext-curl gelesen. extra.version ist kein selbstständiger Beleg für einen Release.

### S17 - libcurl-Maintainer zur Zielauflösung hinter HTTP-Proxies

Quelle: https://curl.se/mail/lib-2018-03/0024.html

Primärquelle des Maintainers: Originauflösung liegt beim HTTP-Proxy; als technische Begründung, nicht als aktueller Vulnerabilitynachweis verwendet.

### S18 - OWASP SSRF Prevention Cheat Sheet

Quelle: https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html

Grundlagen zu Allowlist-/Public-Internet-Fällen, DNS und Netzwerkebene. Produktentscheidungen dieses Entwurfs sind eigene Ableitungen.

### S19 - nr-vault ADR-026: DNS rebinding defence

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Documentation/Developer/Adr/ADR-026-DnsRebindingDefence.rst

Vollständig gelesen; einschließlich ausdrücklicher Änderung durch ADR-038.

### S20 - Guzzle handlers and middleware

Quelle: https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html

Primärreferenz für Handler-/Promise-Vertrag und Middlewarekomposition.

### S21 - AWS: Supporting Amazon VPC services

Quelle: https://docs.aws.amazon.com/whitepapers/latest/ipv6-on-aws/supporting-amazon-vpc-services.html

Offizielle AWS-Referenz zu IMDS an 169.254.169.254 und fd00:ec2::254; abgerufen 2026-10-08.

### S22 - Alibaba Cloud ECS: Instance metadata

Quelle: https://www.alibabacloud.com/help/en/ecs/user-guide/view-instance-metadata/

Offizielle Referenz zu 100.100.100.200; angezeigter Dokumentstand 2026-07-17, abgerufen 2026-10-08.

### S23 - Microsoft Azure: IP address 168.63.129.16

Quelle: https://learn.microsoft.com/en-us/azure/virtual-network/what-is-ip-address-168-63-129-16

Offizielle Referenz zu virtueller Plattform-/WireServeradresse; Anwendungsclientregel ausdrücklich von benötigtem VM-Plattformverkehr unterschieden.


---

<a id="doc-adr-readme"></a>

# Architekturentscheidungen: Index

Stand: 2026-10-08. **Alle 14 ADRs sind vorgeschlagen.** Sie dokumentieren den vollständigen Sollentwurf, nicht bereits erteilte organisatorische Freigaben. Ihre Nummerierung ist unabhängig von den bestehenden ADR-Nummern in nr-vault.

Die zugrunde liegenden Quellen sind in [08 Evidenz und Quellen](#doc-specs-08-evidence-and-sources) versioniert. Die folgenden Entscheidungen ergänzen sich; insbesondere bilden ADR-0003, ADR-0005 und ADR-0010 gemeinsam die Transportgarantie.

| ADR | Entscheidung | Status |
|---|---|---|
| [ADR-0001](#doc-adr-adr-0001-schutzumfang-und-vertrauensgrenzen) | Schutz des ausgehenden HTTP-Pfads, keine PHP-Firewall | Vorgeschlagen |
| [ADR-0002](#doc-adr-adr-0002-bibliothek-und-integrationen) | Gemeinsame Bibliothek statt Vault-Abhängigkeit aller Extensions | Vorgeschlagen |
| [ADR-0003](#doc-adr-adr-0003-middleware-und-kontrollierter-transport) | Zwei Middlewaregrenzen und ein kontrollierter terminaler Transport | Vorgeschlagen |
| [ADR-0004](#doc-adr-adr-0004-fail-closed-und-betriebsmodi) | Enforce als Standard, Observe nur als sichtbarer Migrationsmodus | Vorgeschlagen |
| [ADR-0005](#doc-adr-adr-0005-dns-und-connection-plan) | Geprüfte Adressmenge unmittelbar an die Verbindung binden | Vorgeschlagen |
| [ADR-0006](#doc-adr-adr-0006-clientgebundene-endpoint-grants) | Interne Zugriffe als clientgebundene Endpoint-Grants | Vorgeschlagen |
| [ADR-0007](#doc-adr-adr-0007-unterstuetzte-transporte) | Nur kontrolliertes cURL; Proxies und PHP-Streams nicht in v1 | Vorgeschlagen |
| [ADR-0008](#doc-adr-adr-0008-redirects-und-credential-grenzen) | Redirects pro Hop prüfen und Credentials an ihre Origin binden | Vorgeschlagen |
| [ADR-0009](#doc-adr-adr-0009-normalisierung-und-adressklassen) | Ein kanonischer Zielparser und ein versionierter Adresskorpus | Vorgeschlagen |
| [ADR-0010](#doc-adr-adr-0010-cache-isolation-und-parallelitaet) | Adressmemoisierung erlauben, Berechtigungs- und Verbindungspooling isolieren | Vorgeschlagen |
| [ADR-0011](#doc-adr-adr-0011-vault-streaming-und-cancellation) | Vault-Streaming erhalten, ohne zum PHP-Streamhandler auszuweichen | Vorgeschlagen |
| [ADR-0012](#doc-adr-adr-0012-fehler-und-beobachtbarkeit) | Stabile Ablehnungsgründe ohne neue Secret-Leaks | Vorgeschlagen |
| [ADR-0013](#doc-adr-adr-0013-kompatibilitaet-und-migration) | Explizite Migration statt stiller Umdeutung bestehender Allowlisten | Vorgeschlagen |
| [ADR-0014](#doc-adr-adr-0014-sicherheitsnachweis-und-release-gates) | Sicherheit durch Zielkontakt- und Regressionsevidenz abnehmen | Vorgeschlagen |

## Freigabeverfahren

Zuerst AP-01 und die Sicherheitsinvarianten prüfen. Anschließend Architektur, Security und Betreiberanforderungen gemeinsam freigeben. Annahmen, deren Integrationsnachweis fehlt, werden nicht allein durch Statuswechsel zu bewiesenen Eigenschaften.

Ein beschlossener ADR wird nicht nachträglich umgeschrieben, um eine andere Entscheidung historisch erscheinen zu lassen. Eine spätere Entscheidung erhält einen neuen ADR mit explizitem Verweis auf den abgelösten. Quellcodebefunde aus nr-vault behalten ihren eigenen historischen Status; dieses Paket ändert ihn nicht.

[Zurück zum Paketüberblick](#doc-readme).


---

<a id="doc-adr-adr-0001-schutzumfang-und-vertrauensgrenzen"></a>

# ADR-0001: Schutz des ausgehenden HTTP-Pfads, keine PHP-Firewall

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-001, HG-002, HG-018, HG-039, HG-042  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Eine unzuverlässige URL kann den Server zum Zugriff auf ein internes Ziel veranlassen. Der normale TYPO3-HTTP-Stack ist ein geeigneter gemeinsamer Eingriffspunkt. Andere PHP-Clients und direkte Sockets existieren daneben; auch ein pro Request ersetzter Handler kann die Middleware umgehen. Der Core stellt keine Prozesssandbox bereit. [S01-S03, S18]

## Entscheidung

Das Produkt schützt ausschließlich dokumentierte, geprüfte Sendewege: den integrierten Standardstack und explizit integrierte Clients. Vor einem nicht erlaubten Zielkontakt muss der Guard ablehnen. DNS-Anfragen an den konfigurierten Resolver sind davon getrennt; der Guard verspricht nicht, überhaupt keine Netzwerkpakete zu senden.

Angreifer dürfen URLs, Antworten und DNS ihrer eigenen Zonen beeinflussen. Installierter PHP-Code, Policydateien, TLS-/Resolverkonfiguration und Betriebssystem gehören zur vertrauenswürdigen Basis. Endpoint-Grants verhindern versehentliche Berechtigungserweiterungen, sind aber kein Schutz gegen bösartigen Code im selben Prozess. Der Guard ist auch keine Autorisierung für einzelne Pfade oder Datensätze auf einem erlaubten Server.

Dokumentation und Diagnose verwenden "global" nur im Sinn des erfassten TYPO3-Stacks. Ausgehende Firewallregeln und Dienstauthentifizierung bleiben ergänzende Kontrollen.

## Verworfene Alternativen

**Prozessweite Netzwerksperre in PHP:** ohne Kontrolle aller Netzwerkfunktionen nicht erfüllbar. **Nur URL-Syntaxvalidierung:** kontrolliert weder DNS noch tatsächliche Verbindung. **Nur Netzwerkfirewall:** wertvoll, liefert aber allein keine fachlich getrennten Clientfreigaben.

## Konsequenzen

Das Schutzversprechen ist messbar und nicht irreführend. Nicht integrierte SDKs bleiben eine bewusste Abdeckungslücke; ihre Migration erfordert eigene Adapter. Der Betrieb muss beide Ebenen inventarisieren.

## Nachweis und Abnahme

T001, T003, T035, T074, T077. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine neue Integration oder ein erweitertes Versprechen benötigt einen eigenen Transportnachweis. Eine künftige Prozess-/Netzwerksandbox ist ein separates Produktziel.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0002-bibliothek-und-integrationen"></a>

# ADR-0002: Gemeinsame Bibliothek statt Vault-Abhängigkeit aller Extensions

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-006, HG-036, HG-037, HG-041, HG-045  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

nr-vault besitzt bereits Zielprüfung, DNS-Pinning und einen eigenen Handlerstack. Daneben erfüllt es Aufgaben, die nicht zu einem allgemeinen Netzwerkschutz gehören: Secretzugriff, Credential-Injection, Audit, OAuth sowie spezialisierte Streaming-/Cancellation-APIs. [S04-S08, S16]

## Entscheidung

Vorgeschlagene Aufteilung: `netresearch/http-guard` als eigenständige PHP-Bibliothek und `netresearch/nr-http-guard` als TYPO3-Integration. nr-vault konsumiert die Bibliothek über einen eigenen Adapter. Die Bibliothek bekommt Konfiguration, Resolver, Clock und Reporter injiziert; sie liest keine TYPO3-Globals und kennt keine Vault-Datenbank.

Extrahiert werden Sicherheitsmechanismen und zugehörige Regressionen, nicht ungeprüft jede historische Ausnahme. Vault behält seine credentialtragenden Schnittstellen und Fachverantwortung. Das Librarypaket bietet keinen Secret-Export.

Bei Übernahme bestehenden Vault-Codes bleiben dessen Copyright- und SPDX-Hinweise erhalten. Als kompatibler Projektvorschlag verwenden beide neuen Pakete `GPL-2.0-or-later`; eine anders lizenzierte Veröffentlichung braucht vorher eine gesonderte Rechteklärung. Dieser ADR erteilt keine neuen Rechte.

## Verworfene Alternativen

**Jede Extension hängt von nr-vault ab:** unnötige Fachkopplung. **Dritte Kopie derselben Methoden:** Drift bei Securityfixes. **Kompletter Clientwechsel des Vaults:** gefährdet bewährte Auth-/Streamingsemantik. **Eigenständige Bibliothek erst später:** verfestigt erneut falsche Abhängigkeiten.

## Konsequenzen

Ein Sicherheitsfix erreicht alle integrierten Pfade über denselben Korpus. Es entstehen zwei neue Paketoberflächen und koordinierter Releasebedarf. Abstraktionen bleiben auf die tatsächlichen zwei Integrationen begrenzt; kein allgemeines HTTP-Framework.

## Nachweis und Abnahme

T007, T069, T070, T072, T076, T080. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Ein dritter Konsument kann zusätzliche Adapter motivieren. Eine Erweiterung der Bibliothek um Credentialverwaltung braucht eine neue Entscheidung und ist nicht implizit erlaubt.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0003-middleware-und-kontrollierter-transport"></a>

# ADR-0003: Zwei Middlewaregrenzen und ein kontrollierter terminaler Transport

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-001, HG-002, HG-009, HG-019, HG-021, HG-023  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Der Core installiert eigene Middlewarehandler nach Guzzles Defaults und der optionalen Core-Allowlist. Eine gewöhnliche Vorprüfung vor einem opaken `$next` garantiert nicht, welcher Transport danach läuft. Auch ein nachträgliches Umschreiben von Request-Origin oder Response-Location kann eine frühere Prüfung entwerten. [S01, S05, S12, S20]

## Entscheidung

Der TYPO3-Adapter registriert `nr/http-guard-boundary` als ersten und `nr/http-guard-terminal` als letzten eigenen Middlewareeintrag. Bestehende eigene Middlewares bleiben dazwischen. Boundary erfasst die geprüfte Eingangsorigin; Terminal verifiziert das endgültige Ziel und startet im Enforce-Modus einen kontrollierten Transfer statt den automatisch gewählten Leafhandler.

Originwechsel durch dazwischenliegende Middleware werden abgelehnt. Die Boundary prüft die finale Response nach den eigenen Response-Middlewares und vor Guzzles Redirect-Verarbeitung. Damit wird auch eine nachträglich geänderte Location erfasst. Reguläre Redirects laufen erneut durch den Stack.

Reihenfolge und Einmaligkeit sind Invarianten. Ein bestehender `HandlerStack` als Globalkonfiguration oder ein Eintrag hinter Terminal ist nicht still kompatibel. AP-01 muss den konkreten Registrierungsweg im echten Bootstrap beider TYPO3-Versionen belegen; der Entwurf behauptet kein passendes, ungeprüftes Core-Event.

## Verworfene Alternativen

**Eine reine Vorprüfungsmiddleware:** unkontrollierter Transport. **Interne Corefactory dekorieren/Xclass:** unnötige Abhängigkeit von interner API. **Ganzen Client ersetzen:** verliert vorhandene Middlewaresemantik. **Ein terminaler Guard ohne Boundary:** kann spät geänderte Redirectantworten nicht kontrollieren.

## Konsequenzen

Der dokumentierte Erweiterungspunkt bleibt der Einstieg; die Transportgarantie ist stärker als ein Hostfilter. Die Lösung ist bewusst nicht vollständig transparent: feste Reihenfolge, kein beliebiger Leafhandler und nachzuweisende Bootstrapkompatibilität. Das ist das größte technische Freigabegate.

## Nachweis und Abnahme

T001, T002, T003, T004, T048, T083. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Scheitert die Integrationsprobe, wird dieser ADR ersetzt. Eine unkontrollierte `$next`-Delegation darf nicht als gleichwertige Ersatzlösung freigegeben werden.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0004-fail-closed-und-betriebsmodi"></a>

# ADR-0004: Enforce als Standard, Observe nur als sichtbarer Migrationsmodus

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-003, HG-004, HG-021, HG-033, HG-034, HG-043  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Eine neu installierte Securityextension erzeugt eine Schutzannahme. Ein stiller Fallback ohne cURL oder bei ungültiger Policy würde diese Annahme verletzen. Gleichzeitig braucht ein Bestandsprojekt eine kontrollierte Inventarisierung seiner internen Verbindungen. Vault bietet heute absichtlich ein schwächeres Verhalten ohne cURL. [S04-S06, S16]

## Entscheidung

Neue Installation: `enforce`, keine internen Grants. Fehlende optionale Konfiguration verwendet sichere Defaults; ungültige vorhandene Konfiguration scheitert. Nicht unterstützte Transportbedingungen blockieren vor Kontakt zum Ziel oder Proxy.

`observe` ist eine explizite Betreiberentscheidung. Er protokolliert soweit prüfbar `would_deny` bzw. `unverifiable`, delegiert aber an den bisherigen Transport und darf nicht als geschützt gelten. Ein Diagnoseproblem soll dort nicht als erfolgreiche Sicherheitsprüfung erscheinen. Vorhandene Core-/Vaultkontrollen bleiben unangetastet.

`disabled` ist transparent und sichtbar. Es gibt keinen automatischen Wechsel von Enforce zu Observe oder Disabled. Rollback und Ausnahmen benötigen Änderung an vertrauenswürdiger Projektkonfiguration. Kein Schalter aus einem HTTP-Parameter oder entfernten Responseheader.

## Verworfene Alternativen

**Observe als Installationsdefault:** liefert zunächst keinen erwarteten Schutz. **Warnung statt Block bei Transportlücken:** schwer erkennbarer Verlust der Invariante. **Fail-open bei Konfigurationsfehlern:** ein Tippfehler würde Berechtigungen erweitern.

## Konsequenzen

Fehler sind eindeutig; Bestandsinstallationen können aber nach Aktivierung ausfallen, bis legitime interne Zugriffe erfasst sind. Der Rollout braucht Inventar, Diagnose und bewusste Freigabe. Observe-Telemetrie ist keine Abnahme des Enforce-Transports.

## Nachweis und Abnahme

T005, T006, T042, T065, T066, T067. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine Änderung des Installationsdefaults ist eine produktweite Security-/Kompatibilitätsentscheidung. Automatische Herabstufung bleibt unabhängig davon unzulässig.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0005-dns-und-connection-plan"></a>

# ADR-0005: Geprüfte Adressmenge unmittelbar an die Verbindung binden

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-013, HG-014, HG-015, HG-016, HG-027, HG-044  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Vorprüfung und unabhängige Transportauflösung können verschiedene IPs liefern. Vault hat dies durch `CURLOPT_RESOLVE` adressiert und anschließend Leerantworten geschlossen behandelt. Es behält jedoch eine Literal-Allowlist-Ausnahme bei. DNS und NSS-/Hosts-Auflösung sind nicht gleichwertig. [S05-S07, S09, S19]

## Entscheidung

Jeder Versuch erzeugt nach Normalisierung und Policyprüfung einen unveränderlichen ConnectionPlan. Alle verwertbaren A-/AAAA-Adressen werden geprüft; ein verbotener Kandidat verwirft den gesamten Versuch. Keine brauchbare Antwort bedeutet Ablehnung, auch bei einem freigegebenen Host. Statische Hostzuordnungen liefern ebenfalls zu prüfende Adressen, keine Blankofreigabe.

Der Transport bekommt genau diese Menge in einem Multi-Address-Pin pro Host-Port-Paar. Originalhostname, TLS-SNI und Zertifikatsprüfung bleiben erhalten. Ein unerreichbarer Pin darf keinen ungeprüften DNSfallback auslösen. Ungültige Records, Pinformate oder fehlschlagende Optionsetzer dürfen nicht still ignoriert werden.

Auflösungsumfang und Memoisierung sind begrenzt. Ein synchrones `dns_get_record()` wird nicht als hart unterbrechbarer Resolver ausgegeben; Betriebssystemgrenzen und gemessenes Verhalten gehören zur Abnahme.

## Verworfene Alternativen

**Nur frisches DNS vor jedem Send:** bleibt ein Check-to-connect-Fenster. **Nur gefährliche IPs herausfiltern:** verdeckt gemischte Vertrauenszonen und wird für v1 abgelehnt. **Expliziter Host darf bei DNSfehler unkontrolliert weiter:** verletzt die verbindungsgebundene Garantie. **URL durch IP ersetzen:** erschwert korrekte Host-/TLS-Semantik.

## Konsequenzen

Die Auswahl ist nachvollziehbar und testbar. Split-DNS-Konfigurationen mit gemischten erlaubten/verbotenen Antworten werden bewusst verweigert. Nicht in DNS bekannte Namen brauchen eine statische Zuordnung oder einen später zertifizierten Resolveradapter.

## Nachweis und Abnahme

T022, T023, T024, T025, T026, T027, T028, T029, T030, T031, T032, T033, T078. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Weitere Resolver dürfen aufgenommen werden, wenn sie Kandidaten vollständig und begrenzt liefern und niemals die Transportauflösung unkontrolliert freigeben.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0006-clientgebundene-endpoint-grants"></a>

# ADR-0006: Interne Zugriffe als clientgebundene Endpoint-Grants

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-012, HG-017, HG-018, HG-019, HG-035, HG-038  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Interne ERP-, Such- oder LLM-Dienste sind legitime Ziele. Eine globale Hostfreigabe würde aber auch dem frei bedienbaren URL-Importer denselben Zugriff eröffnen. TYPO3-Kontexte liefern im bestehenden Factorycode keine automatisch an beliebige Middleware weitergereichte Autorisierung. Vaults flache Allowlist hat andere Semantik als die Core-Kontextliste. [S01, S03-S05]

## Entscheidung

Ein Profil bindet exakte Scheme-/Host-/Port-Origin, Methoden, zugelassene CIDRs, Zweck, Verantwortlichkeit und Ablauf. Die Anwendung erhält einen bereits gebundenen Client per vertrauenswürdiger Serviceverdrahtung. Eine bloße URL oder ein vom Benutzer gewählter Profilname aktiviert kein Grant.

Registry-eigene, nicht serialisierbare Grantobjekte sind an Profil und Policygeneration gebunden. Der interne ConnectionPlan ist nicht als dauerhafter Sendetoken exportierbar. Eine Diagnosefreigabe aus `policy-check` ist kein Grant.

Core-Allowlist, harte/global konfigurierte Verbote und Endpointprofil gelten kumulativ. Betreiber-Deny hat Vorrang. Private Netze brauchen enge CIDRs; Loopback zusätzlich ein ausdrückliches Flag und Hostpräfix. Metadaten-/Link-Local- und andere harte Verbote haben keinen pauschalen Allow-Schalter.

## Verworfene Alternativen

**Global `allow_private=true`:** zu breit. **Automatische Freigabe jedes konfigurierten Hostnamens:** löst das Confused-Deputy-Problem nicht. **Stringkontext als Geheimnis:** kopierbar und keine belastbare Bindung. **Prozessweites "aktueller Kontext":** fehleranfällig bei parallelen Requests.

## Konsequenzen

Interne Integrationen bleiben möglich, ohne Public-Fetch aufzuwerten. Sie erfordern ausdrückliche Clientinjektion. Grants sind eine Anwendungsarchitekturregel, keine harte Isolation gegen Codeausführung im selben PHP-Prozess.

## Nachweis und Abnahme

T021, T034, T035, T036, T037, T038, T039, T068, T073. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine neue Klasse interner Ziele oder flexiblere Profilsyntax braucht ein Threat-Model-Update. Wildcards und Netzbereichsfreigaben für alle Aufrufer sind kein beiläufiges Komfortfeature.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0007-unterstuetzte-transporte"></a>

# ADR-0007: Nur kontrolliertes cURL; Proxies und PHP-Streams nicht in v1

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-005, HG-020, HG-021, HG-022, HG-028, HG-042  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Ein cURL-Pin wird von PHP-Streams nicht umgesetzt. Bei Proxybetrieb kann die Originauflösung beim Proxy liegen; die lokal geprüfte IP ist dann kein Beweis für dessen Zielverbindung. Guzzle-Majors unterscheiden sich in erlaubten Raw-Optionen. [S05, S08, S12, S13, S17]

## Entscheidung

Enforce verlangt einen getesteten cURL-/curl-multi-Adapter. `stream=true`, fremde Handler, rohe cURL-Optionen und nicht zertifizierte Transportsharing-Einstellungen werden abgelehnt. cURL-Konstanten und Verhalten werden auf tatsächliche Verfügbarkeit geprüft. Die funktionale Multi-Address-Mindestversion allein ist keine akzeptierte Securitybaseline.

Version 1 unterstützt keine Proxies. Explizite und tatsächlich geerbte Proxykonfiguration wird erkannt; weder Versand darüber noch stilles Umgehen ist erlaubt. Nach bestandener Prüfung unterbindet der kontrollierte Adapter unbeabsichtigtes Proxyerben. Ein eingehender HTTP-Header `Proxy` ist keine vertrauenswürdige Prozesskonfiguration.

Die gemeinsame Bibliothek enthält getrennte, getestete Guzzle-7/8-Optionenadapter. Transportumlenkende Optionen bleiben Guard-eigen. CA-Bundles, mTLS und freigegebene Timeouts werden nicht durch rohe Overrideoptionen ersetzt.

## Verworfene Alternativen

**Alle Guzzlehandler zulassen:** keine einheitliche Garantie. **Proxy als privaten Endpoint allowlisten:** prüft nur den Proxy, nicht dessen Originzugriff. **Automatischer Direct-Fallback:** umgeht Unternehmensrouting. **Jedes beliebige Raw-cURL-Flag durchreichen:** öffnet unabhängige Ziel-/Resolverwege.

## Konsequenzen

Der v1-Umfang ist klar und prüfbar, schließt aber Proxyinstallationen aus. Ein Supportfehler ist sichtbar statt still unsicher. Die Entwicklung muss echte Versionskombinationen testen, nicht nur Composerauflösung.

## Nachweis und Abnahme

T040, T041, T042, T043, T044, T058, T080, T084. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Ein Proxyadapter braucht eine eigene ADR, ein definiertes Trust-Modell und einen Nachweis am tatsächlichen Originpfad. PHP-Streams benötigen ebenfalls eine eigenständig nachgewiesene Bindung vor Freigabe.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0008-redirects-und-credential-grenzen"></a>

# ADR-0008: Redirects pro Hop prüfen und Credentials an ihre Origin binden

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-017, HG-023, HG-024, HG-025  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Eine erlaubte Start-URL kann auf ein internes Ziel umleiten. Selbst bei ausschließlich öffentlichen Zielen können Bodies oder eigene Authheader vertrauliche Inhalte tragen. Eine generische Middleware kann diese Geheimnisse nicht zuverlässig erkennen. Guzzle verarbeitet Redirects außerhalb der eigenen Handler und PSR-18-Sends folgen nicht automatisch. [S01, S05, S15, S20]

## Entscheidung

Im generischen Client gilt Same-Origin. Jede verfolgte Location wird nach den Response-Middlewares geprüft; der Folgeversuch braucht einen neuen ConnectionPlan. Scheme, Host und effektiver Port bilden die Origin. HTTPS-Downgrades sind verboten. Ein Profil kann Follow ganz ausschließen.

Ein gesonderter Public-Fetch-Client darf Cross-Origin folgen, aber nur als GET/HEAD ohne Body, Cookies, Clientzertifikat, Autorisierung oder frei eingespeiste Header. Dieser Client darf keine globalen Credentialdefaults erben. Die Folgeadresse muss erneut zur Public-Policy passen.

Der effektive Follow-Modus und die Hopgrenze werden gegen die Betreiberpolicy validiert. Ein zu großes bereits vom äußeren Guzzle-Redirectcode erfasstes Limit wird abgelehnt, nicht durch eine wirkungslose innere Optionsänderung scheinbar begrenzt. Eigene Wrapper setzen korrekte Limits bei Client-/Requestaufbau. `allow_redirects=false` und PSR-18 geben 30x unverändert zurück.

Retrylogik bleibt beim Aufrufer; jeder echte neue Versuch wird erneut autorisiert. Policyfehler sind kein Anlass für automatische Retries.

## Verworfene Alternativen

**Nur private Redirectziele sperren:** verhindert keine Credentialweitergabe an öffentliche Angreiferziele. **Bekannte Authheader entfernen:** übersieht eigene Header und Bodydaten. **Alle Redirects sperren:** unnötig für Same-Origin/Public-Fetch. **Inneres Clamp ohne Wirkung auf äußeren Redirectzustand:** falsches Sicherheitsversprechen.

## Konsequenzen

Generische Requests mit legitimen Cross-Origin-Redirects können brechen. Die sichere Sonder-API hat bewusst eine kleine Eingabeoberfläche. Methodenwechsel und Hoplimits müssen pro Major konkret getestet werden.

## Nachweis und Abnahme

T045, T046, T047, T048, T049, T050, T051, T052, T081. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine API für credentialtragende Cross-Origin-Redirects wäre ein neues Autorisierungsmodell und braucht explizite Ziel-/Credentialfreigaben sowie einen eigenen ADR.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0009-normalisierung-und-adressklassen"></a>

# ADR-0009: Ein kanonischer Zielparser und ein versionierter Adresskorpus

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-007, HG-008, HG-009, HG-010, HG-011, HG-012  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Parser interpretieren nichtkanonische numerische Adressen unterschiedlich. Hostheader und URI können voneinander abweichen. Ein grober Private-IP-Filter deckt nicht alle lokalen oder speziellen Adressklassen ab. IANA führt eigenständige IPv4-/IPv6-Spezialregister. [S04, S05, S10, S11]

## Entscheidung

Der Guard normalisiert einmal, verwendet danach ein typisiertes Target und wendet dieselbe Authority auch beim Transport an. Absolute HTTP-/HTTPS-URLs sind Pflicht; Userinfo, Fragmente, Steuerzeichen, Zone-IDs, Host-Prozentkodierung und nichtkanonische numerische Formen werden abgewiesen. HTTP-Host und URI-Authority müssen nach Normalisierung übereinstimmen. Unicodehostnamen werden in v1 nicht implizit konvertiert; bereits kanonisch umgewandelte ASCII-IDNA-Namen sind zulässig.

IP- und CIDR-Prüfungen erfolgen binär für beide Familien. IPv4-mapped IPv6 wird auf die eingebettete IPv4-Policy zurückgeführt. Tunnel-/Übersetzungsbereiche werden konservativ gesperrt. Der versionierte Korpus benennt Privaträume, Loopback, Link-Local, Metadatenrelevanz, Multicast, Dokumentations-/Benchmark- und weitere Spezialbereiche.

Die v1-Public-Policy ist bewusst konservativer als eine bloße positive Globally-Reachable-Markierung einzelner Spezialadressen. Betreiber-Deny kann zusätzlich auch nominell öffentliche, intern geroutete Bereiche sperren. Registerupdates werden versioniert, nie bei jedem Request live heruntergeladen.

## Verworfene Alternativen

**Nur Regex:** ungeeignet als umfassende IP-/CIDRentscheidung. **Nur PHP-Filterflags:** koppelt Policy unbemerkt an deren konkrete Laufzeitsemantik. **PrivateIPv4 ohne IPv6:** lässt eine zweite Adressfamilie offen. **Jede Parserkorrektur akzeptieren:** vergrößert Interpretationsunterschiede.

## Konsequenzen

Es gibt einen testbaren Entscheidungsweg und nachvollziehbare Datensatzversionen. Einzelne legitime Spezialadressen und Unicodeeingaben sind eingeschränkt; Aufrufer müssen kanonische Ziele liefern. Öffentliche IP bedeutet weiterhin nicht "inhaltlich vertrauenswürdiger Server".

## Nachweis und Abnahme

T008, T009, T010, T011, T012, T013, T014, T015, T016, T017, T018, T019, T020, T021. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine Lockerung einer gesperrten Adressklasse oder Parserform braucht Korpusänderung, Begründung und Grenztests. Neue IANA-Einträge werden wie sicherheitsrelevante Abhängigkeitsänderungen behandelt.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0010-cache-isolation-und-parallelitaet"></a>

# ADR-0010: Adressmemoisierung erlauben, Berechtigungs- und Verbindungspooling isolieren

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-015, HG-025, HG-026, HG-027, HG-040, HG-044  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Mehrere gleichzeitige Requests können dieselbe Origin unter unterschiedlichen Freigaben oder DNSantworten erreichen. Guzzles/cURLs Pooling und optionale Share-Handles können DNS- oder Verbindungszustand wiederverwenden. Ein neuer Pin allein ist deshalb kein ausreichender Nachweis requestbezogener Isolation. [S12, S14]

## Entscheidung

Version 1 isoliert jede unabhängige Transferlease samt cURL-Multi-Handle, DNS- und Connectionzustand. Keine gemeinsamen Share-Handles, keine Übernahme fremder Poolverbindungen, keine stillen Alt-Svc- oder HSTS-Routenwechsel. Ein Request mit internem Grant darf keinen späteren Public-Request über seinen Verbindungspool privilegieren.

Ein begrenztes positives DNSmemo ist erlaubt: standardmäßig höchstens fünf Sekunden und 32 Hosts, bei kürzerem bekannten TTL entsprechend weniger. TTL 0 und negative Antworten werden nicht positiv gecacht. Schlüssel enthalten Resolveridentität/-konfiguration. Jede Verwendung klassifiziert die Adressen neu gegen die aktuelle Policy; es wird niemals ein boolesches Allow gecacht.

ConnectionPlans sind einmalige, kurzlebige Versuchsdaten. Verzögerte Arbeit autorisiert beim tatsächlichen neuen Versuch, nicht bei ihrer Einplanung. Policywechsel gelten für neue Versuche; bereits laufende erlaubte Transfers werden nicht als automatisch widerrufen ausgegeben.

## Verworfene Alternativen

**Gemeinsamer Pool plus Pin:** erfordert zusätzliche, komplexe Isolationsevidenz. **Globaler Host-Allow-Cache:** vermischt Kontexte. **Gar keine Memoisierung:** sicher möglich, verursacht aber vermeidbare Doppelauflösung. **Live-Widerruf laufender Transfers:** eigenes Laufzeitfeature, nicht durch einen Cacheflush erledigt.

## Konsequenzen

Parallele Sicherheitsentscheidungen bleiben voneinander unabhängig. v1 verzichtet auf Wiederverwendung über unabhängige Versuche und akzeptiert Handshakekosten. Bei hohem Requestvolumen muss dies gemessen werden; die Bibliothek puffert Bodies nicht zusätzlich.

## Nachweis und Abnahme

T038, T052, T053, T054, T055, T056, T057, T075, T079, T084. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Pooling kann erst nach einer neuen ADR mit Schlüssel-/Invalidierungsmodell, Concurrencytests und Wire-Beweis eingeführt werden. Ein Performanceziel allein ist kein Nachweis.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0011-vault-streaming-und-cancellation"></a>

# ADR-0011: Vault-Streaming erhalten, ohne zum PHP-Streamhandler auszuweichen

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-028, HG-029, HG-030, HG-036, HG-037  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Vaults ADR-039 beschreibt Streaming über einen gepinnten curl-multi-Transfer, dessen Fortschritt beim Lesen des Responsebodys getrieben wird. `stream=true` würde dagegen den PHP-Streamhandler auswählen und den Pin verlieren. Die credentialtragende API kapselt Transport, Promise und Secrets bewusst. [S05, S08]

## Entscheidung

Die globale Middleware unterstützt in v1 normale gebufferte Sends und lehnt Guzzles `stream=true` explizit ab. Das schließt Vaults gesonderte Streaming-API nicht aus. Deren Adapter verwendet dieselbe Policy-/ConnectionPlanlogik, erhält aber eine intern kontrollierte Transferlease für laufenden Fortschritt und Cancellation.

Ein einziger Owner verantwortet Tickschleife, Settlement und Freigabe. Vorab-Cancellation verhindert den Start; Cancellation im Flug schließt den aktiven Socket. Body-close, Fehler und abgebrochener Konsum geben Ressourcen idempotent frei. Fehler nach Teilantworten werden nicht als vollständiger EOF-Erfolg ausgegeben.

Vault behält Credential-Injection, vorherige Secretberechtigungen, Audit, Buffergrenzen, Idle-/Gesamtzeitsemantik und die Unterscheidung zwischen Proxy- und Originantworten in seinen bestehenden Regressionen. Die neue v1-Proxygrenze wird als explizite Kompatibilitätsänderung behandelt, nicht versteckt. Seine öffentliche API exportiert weiterhin keine rohen Securityoptionen.

## Verworfene Alternativen

**Streaming ganz streichen:** unnötiger Produktverlust. **`stream=true` akzeptieren:** falscher Transport. **Streamingmechanismus neu kopieren:** vermeidbare Fehler bei EOF, Bufferlimits und Cancellation. **Rohen Client herausgeben:** vergrößert die credentialtragende Oberfläche.

## Konsequenzen

Die anspruchsvolle Vaultfunktion bleibt erhalten. Die Migration verlangt reale Streaming-/Cancellationtests und ist nicht allein durch das Austauschen einer Factory abgeschlossen. Lange Streams bleiben ressourcenbehaftete, explizit zu beendende Transfers.

## Nachweis und Abnahme

T058, T059, T060, T061, T069, T070, T071, T072. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Globales inkrementelles Streaming ist ein späteres Feature. Es muss dieselben Pins, Lifecycle- und Fehlerverpflichtungen erfüllen, statt nur einen anderen Guzzle-Schalter freizugeben.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0012-fehler-und-beobachtbarkeit"></a>

# ADR-0012: Stabile Ablehnungsgründe ohne neue Secret-Leaks

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-031, HG-032, HG-033, HG-034, HG-035  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Ein Sicherheitsguard braucht Diagnose, kann aber durch Logs selbst Credentials offenlegen. URLs können Querytokens tragen; Requests enthalten Bodies, Cookies und Authheader. Ein nach Versand ausgeführter Statistikcallback ist außerdem keine präventive Verbindungskontrolle. [S04, S08, S15]

## Entscheidung

Die Bibliothek liefert feste Reason-Codes mit typisierter Exceptionoberfläche. Synchrone und Promise-basierte Aufrufe unterscheiden sich nicht in der Policyentscheidung. Policyfehler werden nicht als normale Netzwerktimeouts getarnt. PSR-18 erkennt sie als Clientfehler; der enthaltene Request bleibt gegebenenfalls ein sensitives Objekt.

Telemetrie enthält Modus, Entscheidung, Profil-/Policykennung, Adressklasse, Scheme/Port, Resolverquelle und Korrelation. Keine Bodies, Headerwerte, URLpfade, Queries oder kompletten Exceptions. Hostanzeige ist separat konfiguriert; pseudonyme Hostwerte verwenden einen Betreiber-HMAC-Schlüssel, keinen öffentlich erratbaren Hash als angebliche Anonymisierung. Metriklabels bleiben niedrigkardinal.

Loggerfehler dürfen nie aus Deny ein Allow machen. Rate-Limits reduzieren Logfluten, nicht Denialzähler. Diagnosekommandos senden keine Probe-HTTP-Requests. `on_stats` darf eine Abweichung nachträglich melden, ersetzt aber niemals den Plan/Pinschutz.

## Verworfene Alternativen

**Vollständige Requests zur Fehlersuche loggen:** schafft Exfiltrationspfad. **Jede Ablehnung als Angriff etikettieren:** verwechselt Tippfehler, DNSprobleme und Missbrauch. **Logausfall generell als HTTP-Deny:** unnötige globale Verfügbarkeitskopplung; Vaultaudit kann separat strenger sein.

## Konsequenzen

Betrieb kann Ursachen erkennen, ohne Geheimnisse offenzulegen. Tiefere Fehlersuche erfordert gezielte synthetische Reproduktion. Andere Projektmiddlewares bleiben für ihre eigenen Logs verantwortlich; der Guard kann deren Telemetrie nicht rückwirkend bereinigen.

## Nachweis und Abnahme

T062, T063, T064, T065, T067, T068. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Neue Telemetriefelder werden vor Aufnahme auf Geheimnisgehalt und Kardinalität geprüft. Ein kompletter Requestdump ist kein zulässiger Debugkomfort.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0013-kompatibilitaet-und-migration"></a>

# ADR-0013: Explizite Migration statt stiller Umdeutung bestehender Allowlisten

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-005, HG-036, HG-037, HG-038, HG-043, HG-045  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Vault benutzt einen separaten Stack, erlaubt exakte flache Hosteinträge als Ausnahme und führt ext-curl nur als Vorschlag. TYPO3s geprüfter Corestand verwendet verschachtelte Kontextallowlists und erlaubt Guzzle 7 und 8. Eine naive Extraktion kann damit zugleich Schutzgrenzen und Kompatibilität verändern. [S01, S04-S06, S13, S16]

## Entscheidung

Zuerst werden gemeinsamer Korpus und Bibliothek implementiert, dann Coreintegration und expliziter Vaultadapter. Keine Installation der globalen Extension gilt automatisch als Vaultmigration. OAuth-Tokenleg und Resourceleg erhalten getrennte Autorisierung und Pins.

Ein Legacyreport erfasst vorhandene Konfiguration, schreibt aber keine aktive interne Freigabe. Betreiber müssen Scheme, Port, CIDRs, Methoden, Zweck, Verantwortung und Ablauf bewusst ergänzen. Leere DNSantworten werden nicht mehr durch Namensfreigabe legitimiert; statische Mappings ersetzen notwendige Hosts-/NSS-Sonderfälle.

Versionswechsel und Releasehinweise benennen neue cURLpflicht, Proxy-/Streamgrenzen und strengere Ausnahmen. Entfernen bestehenden Legacyverhaltens erfolgt ausdrücklich versioniert. Die Bibliothek hat keinen versteckten Legacy-Fail-open-Schalter. Die unterstützten PHP-/TYPO3-/Guzzle-/PSR-Kombinationen werden durch aufgelöste Locks und tatsächliche CI belegt.

## Verworfene Alternativen

**Alte Listen automatisch konvertieren:** wichtige Berechtigungsdimensionen fehlen. **Globale Middleware als Vaultschutz deklarieren:** technisch falsch. **Securityfix still mit Architekturmigration verbinden:** erschwert Rollback und Review. **Alle Major-Kombinationen versprechen, die Composer erlaubt:** ersetzt keinen Transporttest.

## Konsequenzen

Migration ist nachvollziehbar und rollbackfähig, aber nicht konfigurationsfrei. Legacy und neuer Pfad können zeitlich getrennt verfügbar sein; Diagnose muss klar zeigen, welcher aktiv ist. Secrets-/Auditregressionen sind eigene Freigabekriterien.

## Nachweis und Abnahme

T039, T041, T042, T069, T070, T071, T072, T073, T080, T082. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Neue Core-/Guzzle-Majors oder eine Änderung des globalen Erweiterungspunkts erfordern erneute Integrationsabnahme. Ein gepflegter Legacyzweig wird nicht als gleichwertiger Schutz beworben.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-adr-adr-0014-sicherheitsnachweis-und-release-gates"></a>

# ADR-0014: Sicherheit durch Zielkontakt- und Regressionsevidenz abnehmen

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-039, HG-040, HG-041, HG-044, HG-045  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Ein Test kann grün sein, obwohl er nur eine Exception nach bereits versandtem Request beobachtet. DNSrebinding, Redirects und geteilte Pools sind mit reinen Unitmocks nicht ausreichend belegt. Bestehende Vault-ADRs unterscheiden bereits Transport-nicht-erreicht-Tests und echte Wire-Nachweise. [S06, S08, S19]

## Entscheidung

Der normative Korpus enthält 84 Testfälle und ordnet alle 45 Anforderungen zu. Jeder kritische Ablehnungsfall braucht einen negativen Zielkontaktbeweis: zuerst Transportspy, für Transportinvarianten zusätzlich instrumentierte, hermetische Zielserver bzw. Netznachweis. Die Testumgebung darf keine realen Cloudmetadaten- oder fremden internen Dienste ansprechen.

Testschichten: Unit/Property, Libraryintegration, echte TYPO3-Registrierung, echte cURL-Netztests, Concurrency/Worker sowie Vaultnormal-/OAuth-/Streaming-/Cancellationpfade. Grenzfälle verwenden denselben Korpus. Isolierte Netze und injizierte Resolver machen Antworten reproduzierbar; keine Internetabhängigkeit für Freigabetests.

Gezielte Mutationen entfernen z.B. Pin oder Adressprüfung; die zugehörigen Tests müssen dann scheitern. Release-Gates verlangen erst Integrationsprobe, dann vollständigen P0-Nachweis und unabhängiges Securityreview. Ein neuer Dependencymajor oder Sicherheitsfix startet die relevanten Gates erneut.

## Verworfene Alternativen

**Nur Coverageprozent:** sagt nichts über die behauptete Invariante. **Nur Exceptions prüfen:** kann zu spät sein. **Nur manuelle Test-URL:** nicht reproduzierbar und potenziell gefährlich. **Vorhandene ADR-Messungen als neue Evidenz ausgeben:** verwechselt Quelle und ausgeführte Verifikation.

## Konsequenzen

Die Abnahme ist auf konkrete Garantien zurückführbar. Ein Testharness und mehrere Laufzeitkombinationen kosten Aufwand, verhindern aber unbemerkte Regressionen. Dieses Dokumentationspaket allein erfüllt diese Produktgates noch nicht.

## Nachweis und Abnahme

T001-T084; besonders T029-T033, T043-T048, T053-T061, T074-T080. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](#doc-specs-06-verification). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Neue Angriffswege, Transportoptionen oder Konsumenten erweitern den Korpus vor ihrer Freigabe. Ein nicht reproduzierbares Sicherheitsversprechen muss eingeschränkt statt werblich aufrechterhalten werden.

## Zugehörige Dokumente

[Produktanforderungen](#doc-specs-01-product-requirements), [Sicherheitsmodell](#doc-specs-02-security-model), [Architektur](#doc-specs-03-architecture), [Quellen S01-S23](#doc-specs-08-evidence-and-sources), [ADR-Index](#doc-adr-readme).


---

<a id="doc-document-qa"></a>

# Dokument-QA

Datum: 2026-10-08. Geprüft wurde das Dokumentationspaket, **nicht eine implementierte Extension**.

| Prüfung | Ergebnis |
|---|---|
| Erwartete Quelldokumente | 24 Markdown-Dateien vorhanden |
| ADRs | 14; alle mit Kontext, Entscheidung, Alternativen, Konsequenzen, Nachweis und Revisionsanlass |
| Status | Alle neuen ADRs ausdrücklich vorgeschlagen |
| Anforderungen | 45 eindeutige IDs HG-001 bis HG-045 |
| Testszenarien | 84 eindeutige IDs T001 bis T084; 74 P0 und 10 P1 |
| Anforderungsabdeckung | Alle 45 Anforderungen mindestens einem Test zugeordnet |
| Traceability | Rückwärtszuordnung unabhängig gegen Testmatrix verglichen |
| Lokale Dokumentverweise | 109 Verweise auf vorhandene Dateien aufgelöst |
| Referenzkennungen | Nur definierte Anforderungen, Tests und Quellenkennungen |
| Codeblöcke | Alle Markdown-Fences paarig |
| Platzhalter | Keine TODO/TBD/FIXME oder nicht expandierten Textmarker |
| Quellen | 23 Primärquellen/-referenzen dokumentiert; Source-Snapshots und Grenzen angegeben |
| Umfang | Rund 16,905 durch Whitespace getrennte Wörter/Tokens in Quelldokumenten |

Manuell nachgeschärft: finale Responseprüfung vor Redirect, Wirkung äußerer Guzzle-Redirectlimits, echte Proxyumgebung gegen CGI-Header, verzögerte Versuche gegen Grantablauf, interne Freigaben gegen Cloud-/Plattform-Hard-Deny.

**Nicht ausgeführt:** Unit-, Integration-, Wire-, Last- oder Securitytests des geplanten Produkts, erneute Vault-Testläufe, vollständiger Repositoryaudit, Integrationsprobe AP-01. Die 84 Tests sind Abnahmeanforderungen und kein grünes Testergebnis.

Die ZIP-Datei wird auf CRC-/Archivintegrität geprüft. Das zusammengeführte Gesamtdokument verwendet interne Anker anstelle relativer Dateiverweise.
