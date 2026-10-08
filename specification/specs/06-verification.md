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
