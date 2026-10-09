# Original requirements and test ledger

This preserves all 45 HG requirements and 84 proposed tests. No production test in this ledger has been implemented or executed by preparing these data. Legacy Vault baseline runs are separate characterization evidence, not evidence that an HG test passes.

P0: 74. P1: 10.

## Requirements

| ID | Minimum original tests | Status |
|---|---|---|
| HG-001 | T001 | not verified |
| HG-002 | T002, T003, T004, T083 | not verified |
| HG-003 | T005, T006 | not verified |
| HG-004 | T005 | not verified |
| HG-005 | T001, T041, T082 | not verified |
| HG-006 | T007 | not verified |
| HG-007 | T008, T010 | not verified |
| HG-008 | T009, T010, T011, T012 | not verified |
| HG-009 | T004, T013 | not verified |
| HG-010 | T014, T015, T016, T020 | not verified |
| HG-011 | T014, T015, T017, T018, T019, T020 | not verified |
| HG-012 | T021 | not verified |
| HG-013 | T022, T023, T028 | not verified |
| HG-014 | T024, T025, T026, T027 | not verified |
| HG-015 | T026, T027, T029, T030, T033, T084 | not verified |
| HG-016 | T030, T031, T032 | not verified |
| HG-017 | T025, T027, T034, T035, T036, T037, T038, T081 | not verified |
| HG-018 | T034, T035, T036 | not verified |
| HG-019 | T002, T004, T021, T039, T083 | not verified |
| HG-020 | T040, T041 | not verified |
| HG-021 | T042 | not verified |
| HG-022 | T043, T044 | not verified |
| HG-023 | T045, T046, T048, T049, T051, T081 | not verified |
| HG-024 | T046, T047, T048, T050 | not verified |
| HG-025 | T052 | not verified |
| HG-026 | T053, T054, T055, T084 | not verified |
| HG-027 | T056, T057 | not verified |
| HG-028 | T058 | not verified |
| HG-029 | T059, T060 | not verified |
| HG-030 | T060, T061 | not verified |
| HG-031 | T062 | not verified |
| HG-032 | T063, T064 | not verified |
| HG-033 | T065, T066 | not verified |
| HG-034 | T067 | not verified |
| HG-035 | T068 | not verified |
| HG-036 | T069, T070 | not verified |
| HG-037 | T059, T060, T070, T071, T072 | not verified |
| HG-038 | T039, T073 | not verified |
| HG-039 | T074 | not verified |
| HG-040 | T053, T054, T075 | not verified |
| HG-041 | T074, T076 | not verified |
| HG-042 | T003, T006, T077 | not verified |
| HG-043 | T038, T066, T073, T080 | not verified |
| HG-044 | T028, T057, T078, T079, T082 | not verified |
| HG-045 | T076, T080 | not verified |

## Tests

| ID | Priority | Requirements | Execution | Scenario |
|---|---|---|---|---|
| T001 | P0 | HG-001, HG-005 | not run | Registrierung in echten TYPO3-13.4-/14.3-Instanzen |
| T002 | P0 | HG-002, HG-019 | not run | Eigene Middleware vor/nach Core-Allowlist |
| T003 | P0 | HG-002, HG-042 | not run | Middleware nach Terminal / doppelte Registrierung / HandlerStack-Objekt |
| T004 | P0 | HG-002, HG-009, HG-019 | not run | Middleware schreibt Origin nach Core-Prüfung um |
| T005 | P0 | HG-003, HG-004 | not run | Leere neue Konfiguration |
| T006 | P0 | HG-003, HG-042 | not run | Falscher Modus / unbekannte Konfigurationsfelder |
| T007 | P1 | HG-006 | not run | Bibliothek ohne TYPO3 und nr-vault installieren |
| T008 | P0 | HG-007 | not run | Kanonischer öffentlicher Host / Großschreibung / ein Endpunkt |
| T009 | P0 | HG-008 | not run | file, gopher, ftp, dict, relative URL, Userinfo, Fragment |
| T010 | P0 | HG-007, HG-008 | not run | Steuerzeichen, Backslash, Prozentkodierung im Host, leere Labels |
| T011 | P0 | HG-008 | not run | Integer-, Hex-, Oktal- und Kurz-IPv4 |
| T012 | P0 | HG-008 | not run | IPv6-Zone-ID und ungültige URI-Klammern |
| T013 | P0 | HG-009 | not run | Abweichender Host-Header oder Port |
| T014 | P0 | HG-010, HG-011 | not run | IPv4 RFC1918 und Loopback direkt |
| T015 | P0 | HG-010, HG-011 | not run | IPv6 ULA, Loopback, Link-Local |
| T016 | P0 | HG-010 | not run | IPv4-mapped IPv6 mit interner und öffentlicher eingebetteter IPv4 |
| T017 | P0 | HG-011 | not run | Cloud-Metadaten IPv4/IPv6, Unspecified, Multicast |
| T018 | P0 | HG-011 | not run | CGNAT, Dokumentation, Benchmark, Reserved |
| T019 | P0 | HG-011 | not run | NAT64, 6to4, Teredo, deprecated compatible |
| T020 | P0 | HG-010, HG-011 | not run | CIDR-Grenzen aller mitgelieferten Bereiche |
| T021 | P0 | HG-012, HG-019 | not run | Betreiber-Deny überlappt Endpoint-Freigabe |
| T022 | P0 | HG-013 | not run | DNS liefert public A und private AAAA |
| T023 | P0 | HG-013 | not run | DNS liefert public AAAA und private A |
| T024 | P0 | HG-014 | not run | NXDOMAIN, SERVFAIL, leere Antwort, ungültige Recorddaten |
| T025 | P0 | HG-014, HG-017 | not run | Freigegebener privater Host ohne verwertbare Auflösung |
| T026 | P0 | HG-014, HG-015 | not run | Nur hosts/NSS bekannter Name, keine statische Zuordnung |
| T027 | P0 | HG-014, HG-015, HG-017 | not run | Statischer Host mit enger interner Freigabe |
| T028 | P0 | HG-013, HG-044 | not run | CNAME zu privat, Schleife, mehr als acht Hops, mehr als 64 Adressen |
| T029 | P0 | HG-015 | not run | Resolver liefert zuerst public, anschließend private |
| T030 | P0 | HG-015, HG-016 | not run | Pin auf nicht erreichbare Adresse |
| T031 | P0 | HG-016 | not run | Mehrere A/AAAA, erste Adresse unerreichbar |
| T032 | P0 | HG-016 | not run | Fehlerhafte Pinzeichenfolge / Optionsetzer schlägt fehl |
| T033 | P0 | HG-015 | not run | TLS-SNI, Zertifikatsname und Host bei Pinning |
| T034 | P0 | HG-017, HG-018 | not run | ERP-Grant gegen andere Origin, Port, Methode oder CIDR |
| T035 | P0 | HG-017, HG-018 | not run | Benutzer setzt Profilstring, Header oder gefälschtes Grantobjekt |
| T036 | P0 | HG-017, HG-018 | not run | Public-Importer ruft konfigurierten ERP-Host auf |
| T037 | P0 | HG-017 | not run | Loopback mit /32 bzw. /128 und explizitem Flag |
| T038 | P0 | HG-017, HG-043 | not run | Ablauf eines Grants, geänderte Policyrevision, verzögert eingeplanter Versuch |
| T039 | P0 | HG-019, HG-038 | not run | Core-Kontextallowlist plus neue Guardpolicy |
| T040 | P0 | HG-020 | not run | Caller liefert RESOLVE, CONNECT_TO, URL, SHARE, Unix-Socket, FOLLOWLOCATION |
| T041 | P0 | HG-020, HG-005 | not run | Guzzle-7/8-Optionen, verbotener delay und Promise-/PSR-7-Majors |
| T042 | P0 | HG-021 | not run | Kein ext-curl, kein curl-multi, inkompatible Version |
| T043 | P0 | HG-022 | not run | HTTP-/HTTPS-/SOCKS-Proxy explizit |
| T044 | P0 | HG-022 | not run | Proxy über echte Umgebung inklusive Case-/NO_PROXY-Varianten; eingehender Proxy-Header separat |
| T045 | P0 | HG-023 | not run | Redirect auf private IP und auf private DNS-Adresse |
| T046 | P0 | HG-023, HG-024 | not run | Same-Origin-Redirect und Cross-Origin-Redirect |
| T047 | P0 | HG-024 | not run | 307/308 mit Body-Secret oder Custom-Auth-Header zu anderer Origin |
| T048 | P0 | HG-023, HG-024 | not run | Response-Middleware verändert Location nach Terminal |
| T049 | P0 | HG-023 | not run | Relative/Schema-relative Location, Downgrade, Schleife, Requestlimit oberhalb/unterhalb Betreibergrenze |
| T050 | P0 | HG-024 | not run | Public-Fetch über mehrere öffentliche Origins |
| T051 | P0 | HG-023 | not run | allow_redirects=false / PSR-18-Send |
| T052 | P0 | HG-025 | not run | Retrymiddleware wiederholt Request nach Netzwerkfehler |
| T053 | P0 | HG-026, HG-040 | not run | Parallel gleicher Host/Port mit unterschiedlicher Policy und IP |
| T054 | P0 | HG-026, HG-040 | not run | Langlebiger Worker, erst interne Freigabe dann public Request |
| T055 | P0 | HG-026 | not run | Externes/persistentes Transport-Sharing |
| T056 | P0 | HG-027 | not run | Memo-Hit bei geänderter Policy |
| T057 | P1 | HG-027, HG-044 | not run | TTL 0, Ablauf, Kapazität, negative Antwort |
| T058 | P0 | HG-028 | not run | stream=true trotz installiertem cURL |
| T059 | P0 | HG-029, HG-037 | not run | Vault-sendStreaming mit gültigem Pin |
| T060 | P0 | HG-029, HG-030, HG-037 | not run | Cancellation vor Send und während Transfer |
| T061 | P0 | HG-030 | not run | Body-close, teilweiser Read, Transferfehler, Exception im Callback |
| T062 | P0 | HG-031 | not run | Synchrone und asynchrone Policyfehler, PSR-18 |
| T063 | P0 | HG-032 | not run | Secrets in Query, Body, Headers, Zertifikatspfaden und Exception |
| T064 | P1 | HG-032 | not run | Loggerausfall und Denial-Flood |
| T065 | P0 | HG-033 | not run | Observe mit verbotenem Ziel / unsupported Transport |
| T066 | P1 | HG-033, HG-043 | not run | Disabled und bewusster Rollback |
| T067 | P1 | HG-034 | not run | Doctor bei normalem/fehlerhaftem Stack |
| T068 | P1 | HG-035 | not run | Policy-check mit und ohne Endpoint |
| T069 | P0 | HG-036 | not run | Nur globale Extension installiert, Vault unverändert |
| T070 | P0 | HG-036, HG-037 | not run | Vault mit bewusst integriertem Adapter |
| T071 | P0 | HG-037 | not run | OAuth-Tokenendpoint intern, Resourceendpoint extern und umgekehrt |
| T072 | P0 | HG-037 | not run | Vault-Authentifizierungsarten und redigiertes Audit |
| T073 | P0 | HG-038, HG-043 | not run | Legacyreport zu flachen/verschachtelten allowed_hosts |
| T074 | P0 | HG-039, HG-041 | not run | Mutation: Prüfung deaktiviert oder Pin entfernt |
| T075 | P0 | HG-040 | not run | Parallelität mit wechselnden DNS-Antworten und Grants |
| T076 | P1 | HG-041, HG-045 | not run | Gemeinsamer Regressionstest in Library, TYPO3 und Vault |
| T077 | P0 | HG-042 | not run | Fremder SDK, direkter cURL, requesteigener Handler |
| T078 | P1 | HG-044 | not run | DNS-Blackhole / langer Resolvercall |
| T079 | P1 | HG-044 | not run | Klassifikation mit 64 Adressen/128 Profilen, große Responses |
| T080 | P0 | HG-043, HG-045 | not run | Abhängigkeitsupdate ändert Handler/Raw-Optionen |
| T081 | P0 | HG-023, HG-017 | not run | POST->GET Redirect bei eingeschränktem Methodenprofil |
| T082 | P1 | HG-005, HG-044 | not run | TLS mit eigener CA, mTLS, verify=false nach Policy |
| T083 | P0 | HG-002, HG-019 | not run | Reihenfolge Request-/Response-Middlewares |
| T084 | P0 | HG-015, HG-026 | not run | cURL-Alt-Svc, HSTS, automatische Protokoll-/Routenwechsel |

## Original mapping discrepancies

None in the minimum-to-matrix direction.
