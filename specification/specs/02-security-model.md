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
