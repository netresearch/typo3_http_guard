# Original plan: consolidated verification ledger

Recorded 2026-10-09. Preserved exactly **45 requirements / 84 scenarios / 8 invariants**. **74/74 P0 and 9/10 P1** have current scenario evidence; T079 remains partial for a named CI reference-system benchmark. G5 legacy advisories, G6 human security review and AP-10 operator pilot remain separate release conditions.

The standalone source-bound matrix passes 122 tests / 2167 assertions in each of eight PHP8.2–8.5/Guzzle7–8 tuples. Actual Core integration passes four exact tuples; frozen Vault source passes both dependency majors. Scope and source hashes are recorded in each component report. Raw URI text erased by an existing PSR18 creator cannot be recovered; policy changes activate through immutable snapshot reconstruction.

All paths below are relative to the delivery root. Detailed behavior, exact source locators and covered limits are in the JSON ledger.

## Aktualisierte Ein-Paket-Lieferung

Die ursprüngliche Paketaufteilung wurde ausdrücklich durch eine Extension mit
enthaltenem Kern und vollständiger Dokumentation ersetzt. Die aktuellen
Nachweise ergänzen die unverändert erhaltenen historischen Protokolle:

- Zwölf PHP-/SDK-Zellen: jeweils126/2180, ohne Skips/Fehler/Failures.
- 18 gezielte Mutanten mit tatsächlichen nativen/HTTP-Zeugen.
- 252 Core-Matrixprozesse:168 Composer+84 echte klassische Installationen;
  210 Wire-Assertions. Die finale ZIP-Neuinstallation ergänzt separat70.
- Vault-Packaging-Smokes19/249+19/250; historische vollständige Suiten
  behalten ausdrücklich ihren ursprünglichen Quellstand.

Die Quellpfade folgen jetzt dem Root-Extension-Layout. Historische Laufmanifeste
werden anhand von `evidence/packaging/source-layout-map.json` zugeordnet.
HG-006 bleibt die Abhängigkeitsgrenze des enthaltenen Kerns; ein eigenständiges
zweites Produktionspaket ist durch die Nutzerentscheidung ersetzt.
Aktuelle Gesamtverweise und genaue Provenienz stehen im JSON-Ledger.

## Tests

| ID | Priority | Status | Exact original scenario | Evidence / remaining gap |
|---|---|---|---|---|
| T001 | P0 | verified_scenarios | Registrierung in echten TYPO3-13.4-/14.3-Instanzen | evidence/typo3-integration/production-matrix-summary.json  |
| T002 | P0 | verified_scenarios | Eigene Middleware vor/nach Core-Allowlist | evidence/typo3-integration/production-matrix-summary.json  |
| T003 | P0 | verified_scenarios | Middleware nach Terminal / doppelte Registrierung / HandlerStack-Objekt | evidence/typo3-integration/production-matrix-summary.json  |
| T004 | P0 | verified_scenarios | Middleware schreibt Origin nach Core-Prüfung um | evidence/typo3-integration/production-matrix-summary.json  |
| T005 | P0 | verified_scenarios | Leere neue Konfiguration | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T006 | P0 | verified_scenarios | Falscher Modus / unbekannte Konfigurationsfelder | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T007 | P1 | verified_scenarios | Bibliothek ohne TYPO3 und nr-vault installieren | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T008 | P0 | verified_scenarios | Kanonischer öffentlicher Host / Großschreibung / ein Endpunkt | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T009 | P0 | verified_scenarios | file, gopher, ftp, dict, relative URL, Userinfo, Fragment | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T010 | P0 | verified_scenarios | Steuerzeichen, Backslash, Prozentkodierung im Host, leere Labels | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T011 | P0 | verified_scenarios | Integer-, Hex-, Oktal- und Kurz-IPv4 | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T012 | P0 | verified_scenarios | IPv6-Zone-ID und ungültige URI-Klammern | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T013 | P0 | verified_scenarios | Abweichender Host-Header oder Port | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T014 | P0 | verified_scenarios | IPv4 RFC1918 und Loopback direkt | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T015 | P0 | verified_scenarios | IPv6 ULA, Loopback, Link-Local | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T016 | P0 | verified_scenarios | IPv4-mapped IPv6 mit interner und öffentlicher eingebetteter IPv4 | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T017 | P0 | verified_scenarios | Cloud-Metadaten IPv4/IPv6, Unspecified, Multicast | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T018 | P0 | verified_scenarios | CGNAT, Dokumentation, Benchmark, Reserved | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T019 | P0 | verified_scenarios | NAT64, 6to4, Teredo, deprecated compatible | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T020 | P0 | verified_scenarios | CIDR-Grenzen aller mitgelieferten Bereiche | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T021 | P0 | verified_scenarios | Betreiber-Deny überlappt Endpoint-Freigabe | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T022 | P0 | verified_scenarios | DNS liefert public A und private AAAA | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T023 | P0 | verified_scenarios | DNS liefert public AAAA und private A | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T024 | P0 | verified_scenarios | NXDOMAIN, SERVFAIL, leere Antwort, ungültige Recorddaten | evidence/dns-transport/execution-manifest.json  |
| T025 | P0 | verified_scenarios | Freigegebener privater Host ohne verwertbare Auflösung | evidence/dns-transport/execution-manifest.json  |
| T026 | P0 | verified_scenarios | Nur hosts/NSS bekannter Name, keine statische Zuordnung | evidence/dns-transport/execution-manifest.json  |
| T027 | P0 | verified_scenarios | Statischer Host mit enger interner Freigabe | evidence/typo3-integration/production-matrix-summary.json  |
| T028 | P0 | verified_scenarios | CNAME zu privat, Schleife, mehr als acht Hops, mehr als 64 Adressen | evidence/dns-transport/execution-manifest.json; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T029 | P0 | verified_scenarios | Resolver liefert zuerst public, anschließend private | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T030 | P0 | verified_scenarios | Pin auf nicht erreichbare Adresse | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T031 | P0 | verified_scenarios | Mehrere A/AAAA, erste Adresse unerreichbar | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T032 | P0 | verified_scenarios | Fehlerhafte Pinzeichenfolge / Optionsetzer schlägt fehl | evidence/dns-transport/execution-manifest.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T033 | P0 | verified_scenarios | TLS-SNI, Zertifikatsname und Host bei Pinning | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T034 | P0 | verified_scenarios | ERP-Grant gegen andere Origin, Port, Methode oder CIDR | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T035 | P0 | verified_scenarios | Benutzer setzt Profilstring, Header oder gefälschtes Grantobjekt | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T036 | P0 | verified_scenarios | Public-Importer ruft konfigurierten ERP-Host auf | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/typo3-integration/production-matrix-summary.json  |
| T037 | P0 | verified_scenarios | Loopback mit /32 bzw. /128 und explizitem Flag | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T038 | P0 | verified_scenarios | Ablauf eines Grants, geänderte Policyrevision, verzögert eingeplanter Versuch | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T039 | P0 | verified_scenarios | Core-Kontextallowlist plus neue Guardpolicy | evidence/typo3-integration/production-matrix-summary.json  |
| T040 | P0 | verified_scenarios | Caller liefert RESOLVE, CONNECT_TO, URL, SHARE, Unix-Socket, FOLLOWLOCATION | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/transport/options-final.log  |
| T041 | P0 | verified_scenarios | Guzzle-7/8-Optionen, verbotener delay und Promise-/PSR-7-Majors | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/transport/options-final.log  |
| T042 | P0 | verified_scenarios | Kein ext-curl, kein curl-multi, inkompatible Version | evidence/dns-transport/execution-manifest.json; evidence/dns-transport/missing-curl/summary.json; evidence/typo3-integration/production-matrix-summary.json  |
| T043 | P0 | verified_scenarios | HTTP-/HTTPS-/SOCKS-Proxy explizit | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T044 | P0 | verified_scenarios | Proxy über echte Umgebung inklusive Case-/NO_PROXY-Varianten; eingehender Proxy-Header separat | evidence/dns-transport/execution-manifest.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T045 | P0 | verified_scenarios | Redirect auf private IP und auf private DNS-Adresse | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T046 | P0 | verified_scenarios | Same-Origin-Redirect und Cross-Origin-Redirect | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T047 | P0 | verified_scenarios | 307/308 mit Body-Secret oder Custom-Auth-Header zu anderer Origin | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T048 | P0 | verified_scenarios | Response-Middleware verändert Location nach Terminal | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T049 | P0 | verified_scenarios | Relative/Schema-relative Location, Downgrade, Schleife, Requestlimit oberhalb/unterhalb Betreibergrenze | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T050 | P0 | verified_scenarios | Public-Fetch über mehrere öffentliche Origins | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T051 | P0 | verified_scenarios | allow_redirects=false / PSR-18-Send | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T052 | P0 | verified_scenarios | Retrymiddleware wiederholt Request nach Netzwerkfehler | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T053 | P0 | verified_scenarios | Parallel gleicher Host/Port mit unterschiedlicher Policy und IP | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T054 | P0 | verified_scenarios | Langlebiger Worker, erst interne Freigabe dann public Request | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T055 | P0 | verified_scenarios | Externes/persistentes Transport-Sharing | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T056 | P0 | verified_scenarios | Memo-Hit bei geänderter Policy | evidence/dns-transport/execution-manifest.json; evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T057 | P1 | verified_scenarios | TTL 0, Ablauf, Kapazität, negative Antwort | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T058 | P0 | verified_scenarios | stream=true trotz installiertem cURL | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T059 | P0 | verified_scenarios | Vault-sendStreaming mit gültigem Pin | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T060 | P0 | verified_scenarios | Cancellation vor Send und während Transfer | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T061 | P0 | verified_scenarios | Body-close, teilweiser Read, Transferfehler, Exception im Callback | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; integrations/nr-vault/evidence/vault-guzzle7-final-unit-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-unit-frozen.log  |
| T062 | P0 | verified_scenarios | Synchrone und asynchrone Policyfehler, PSR-18 | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; Resources/Private/HttpGuard/data/security-corpus/evidence/vault-guard-adapter-wire-complete.log  |
| T063 | P0 | verified_scenarios | Secrets in Query, Body, Headers, Zertifikatspfaden und Exception | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; Resources/Private/HttpGuard/data/security-corpus/evidence/vault-guard-adapter-wire-complete.log  |
| T064 | P1 | verified_scenarios | Loggerausfall und Denial-Flood | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; Resources/Private/HttpGuard/data/security-corpus/evidence/vault-guard-adapter-wire-complete.log  |
| T065 | P0 | verified_scenarios | Observe mit verbotenem Ziel / unsupported Transport | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T066 | P1 | verified_scenarios | Disabled und bewusster Rollback | evidence/typo3-integration/production-matrix-summary.json; Resources/Private/HttpGuard/data/security-corpus/evidence/vault-guard-adapter-wire-complete.log  |
| T067 | P1 | verified_scenarios | Doctor bei normalem/fehlerhaftem Stack | evidence/typo3-integration/production-matrix-summary.json; evidence/typo3-integration/final-execution-source-hashes.json  |
| T068 | P1 | verified_scenarios | Policy-check mit und ohne Endpoint | evidence/typo3-integration/production-matrix-summary.json; evidence/typo3-integration/final-execution-source-hashes.json  |
| T069 | P0 | verified_scenarios | Nur globale Extension installiert, Vault unverändert | evidence/typo3-integration/production-matrix-summary.json  |
| T070 | P0 | verified_scenarios | Vault mit bewusst integriertem Adapter | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T071 | P0 | verified_scenarios | OAuth-Tokenendpoint intern, Resourceendpoint extern und umgekehrt | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T072 | P0 | verified_scenarios | Vault-Authentifizierungsarten und redigiertes Audit | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json  |
| T073 | P0 | verified_scenarios | Legacyreport zu flachen/verschachtelten allowed_hosts | evidence/typo3-integration/production-matrix-summary.json  |
| T074 | P0 | verified_scenarios | Mutation: Prüfung deaktiviert oder Pin entfernt | evidence/transport/mutations/summary.json  |
| T075 | P0 | verified_scenarios | Parallelität mit wechselnden DNS-Antworten und Grants | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T076 | P1 | verified_scenarios | Gemeinsamer Regressionstest in Library, TYPO3 und Vault | integrations/nr-vault/evidence/vault-guzzle7-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle7-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle7-final-frozen-library-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-functional-frozen.log; integrations/nr-vault/evidence/vault-guzzle8-final-tested-source-manifest.json; integrations/nr-vault/evidence/vault-guzzle8-final-frozen-library-manifest.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json; integrations/nr-vault/evidence/vault-guzzle7-shared-unbound.json; integrations/nr-vault/evidence/vault-guzzle8-shared-unbound.json  |
| T077 | P0 | verified_scenarios | Fremder SDK, direkter cURL, requesteigener Handler | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log  |
| T078 | P1 | verified_scenarios | DNS-Blackhole / langer Resolvercall | evidence/dns-transport/policy-portable-g7.log; evidence/dns-transport/policy-portable-g8.log  |
| T079 | P1 | partial_current_evidence | Klassifikation mit 64 Adressen/128 Profilen, große Responses | Resources/Private/HttpGuard/data/security-corpus/evidence/policy-benchmark-final.json; evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log Named CI reference-system benchmark remains external to the current host measurement. |
| T080 | P0 | verified_scenarios | Abhängigkeitsupdate ändert Handler/Raw-Optionen | evidence/dns-transport/execution-manifest.json; verification/evidence/library-matrix/php82g7.results.junit.xml; verification/evidence/library-matrix/php82g8.results.junit.xml; verification/evidence/library-matrix/php83g7.results.junit.xml; verification/evidence/library-matrix/php83g8.results.junit.xml; verification/evidence/library-matrix/php84g7.results.junit.xml; verification/evidence/library-matrix/php84g8.results.junit.xml; verification/evidence/library-matrix/php85g7.results.junit.xml; verification/evidence/library-matrix/php85g8.results.junit.xml; evidence/typo3-integration/production-matrix-summary.json; integrations/nr-vault/EVIDENCE.md  |
| T081 | P0 | verified_scenarios | POST->GET Redirect bei eingeschränktem Methodenprofil | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T082 | P1 | verified_scenarios | TLS mit eigener CA, mTLS, verify=false nach Policy | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T083 | P0 | verified_scenarios | Reihenfolge Request-/Response-Middlewares | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |
| T084 | P0 | verified_scenarios | cURL-Alt-Svc, HSTS, automatische Protokoll-/Routenwechsel | evidence/transport/wire-g7-release.log; evidence/transport/wire-g8-release.log; evidence/typo3-integration/production-matrix-summary.json  |

## Requirements

| ID | Status | Original requirement | Test IDs |
|---|---|---|---|
| HG-001 | scenario_evidence_verified | Die Extension MUSS den dokumentierten ausgehenden Guzzle-Middleware-Erweiterungspunkt verwenden; keine eingehende PSR-15-Middleware. | T001 |
| HG-002 | scenario_evidence_verified | Im geschützten Standardstack MUSS jeder tatsächliche Sendeversuch den Guard unmittelbar vor dem kontrollierten Transport passieren. | T002, T003, T004, T083 |
| HG-003 | scenario_evidence_verified | Der Standardmodus MUSS `enforce` sein; fehlende oder ungültige Konfiguration DARF NICHT zu offenem Versand führen. | T005, T006 |
| HG-004 | scenario_evidence_verified | Neue Installationen MÜSSEN ohne interne Freigaben starten. Bestehende Projektkonfiguration DARF NICHT automatisch in Freigaben umgedeutet werden. | T005 |
| HG-005 | scenario_evidence_verified | Version 1 MUSS TYPO3 13.4 und 14.3 als getrennte Testziele behandeln; konkrete Mindestpatchstände werden durch Composer und CI belegt. | T001, T041, T082 |
| HG-006 | scenario_evidence_verified | Die gemeinsame Bibliothek DARF keine TYPO3- oder nr-vault-Abhängigkeit benötigen. | T007 |
| HG-007 | scenario_evidence_verified | URLs MÜSSEN vor Policy- und DNS-Auswertung einheitlich und strikt normalisiert werden. | T008, T010 |
| HG-008 | scenario_evidence_verified | Nur absolute HTTP-/HTTPS-Ziele mit gültigem Host und Port sind erlaubt. Userinfo, Fragmente, Zone-IDs und uneindeutige numerische IP-Formen MÜSSEN abgelehnt werden. | T009, T010, T011, T012 |
| HG-009 | scenario_evidence_verified | URI-Authority und effektiver HTTP-Host MÜSSEN übereinstimmen; beliebige Host-Header-Umleitungen sind nicht Teil des Standardprodukts. | T004, T013 |
| HG-010 | scenario_evidence_verified | IPv4, IPv6 und IPv4-mapped IPv6 MÜSSEN binär und mit derselben Policy klassifiziert werden. | T014, T015, T016, T020 |
| HG-011 | scenario_evidence_verified | Private, lokale, Link-Local-, Multicast-, Dokumentations-, Benchmark- und weitere ausgeschlossene Spezialbereiche MÜSSEN vom Public-Profil gesperrt werden. | T014, T015, T017, T018, T019, T020 |
| HG-012 | scenario_evidence_verified | Zusätzliche Betreiber-Deny-CIDRs MÜSSEN möglich sein und Vorrang vor Freigaben haben. | T021 |
| HG-013 | scenario_evidence_verified | Jeder verwendbare A-/AAAA-Kandidat MUSS geprüft werden. Ein verbotener Kandidat in einer Antwort MUSS den gesamten Versuch ablehnen. | T022, T023, T028 |
| HG-014 | scenario_evidence_verified | Leere, fehlerhafte oder nicht verwertbare Auflösung MUSS geschlossen scheitern; eine Endpoint-Freigabe DARF dies nicht umgehen. | T024, T025, T026, T027 |
| HG-015 | scenario_evidence_verified | Der Transport MUSS ausschließlich den validierten ConnectionPlan verwenden; eine unabhängige Zielauflösung ist verboten. | T026, T027, T029, T030, T033, T084 |
| HG-016 | scenario_evidence_verified | Mehrere zulässige IPs MÜSSEN in einem gültigen Pin pro Host-Port-Paar erhalten bleiben; Dual-Stack-Fallback bleibt auf diese Menge beschränkt. | T030, T031, T032 |
| HG-017 | scenario_evidence_verified | Interne Ausnahmen MÜSSEN Origin, Port, Methoden, erlaubte IP-Mengen und einen explizit gebundenen Client umfassen. | T025, T027, T034, T035, T036, T037, T038, T081 |
| HG-018 | scenario_evidence_verified | Eine einfache URL, ein frei kopierter Request-Header oder ein vom Benutzer gesetzter Kontextstring DARF keine interne Freigabe aktivieren. | T034, T035, T036 |
| HG-019 | scenario_evidence_verified | Core-Allowlist, globale Verbote und Endpoint-Policy MÜSSEN kumulativ gelten. Keine engere Policy darf durch eine breitere ersetzt werden. | T002, T004, T021, T039, T083 |
| HG-020 | scenario_evidence_verified | Sicherheitsrelevante rohe Transportoptionen von Aufrufern MÜSSEN abgelehnt werden; der Guard erzeugt seine Optionen selbst. | T040, T041 |
| HG-021 | scenario_evidence_verified | Der geschützte Transport MUSS cURL/curl-multi verwenden. Fehlende Unterstützung oder unbekannte Handler MÜSSEN im Enforce-Modus vor Versand fehlschlagen. | T042 |
| HG-022 | scenario_evidence_verified | Explizite und über die Umgebung geerbte Proxykonfiguration MUSS erkannt werden. Version 1 darf nicht über einen Proxy senden und darf ihn nicht still umgehen. | T043, T044 |
| HG-023 | scenario_evidence_verified | Jeder Redirect MUSS erneut vollständig geprüft werden; cURL-eigene, am Stack vorbeilaufende Redirects sind verboten. | T045, T046, T048, T049, T051, T081 |
| HG-024 | scenario_evidence_verified | Redirects mit Credentials MÜSSEN derselben Origin treu bleiben; bei generischem Standardclient gilt konservativ Same-Origin. | T046, T047, T048, T050 |
| HG-025 | scenario_evidence_verified | Jeder Retry MUSS erneut durch die Policy laufen. Policyablehnungen sind nicht retryfähig; Retry-Budgets darf der Guard nicht selbst unbemerkt erhöhen. | T052 |
| HG-026 | scenario_evidence_verified | Connection- und DNS-Caches MÜSSEN zwischen unabhängigen Transfers isoliert sein. Gemeinsame cURL-Share-Handles sind in Version 1 verboten. | T053, T054, T055, T084 |
| HG-027 | scenario_evidence_verified | DNS-Memoisierung DARF nur geprüfte Adressen wiederverwenden, MUSS begrenzt sein und MUSS pro Verwendung erneut gegen die aktuelle Policy prüfen. | T056, T057 |
| HG-028 | scenario_evidence_verified | `stream => true` DARF NICHT auf PHP-Streams ausweichen. In Version 1 des globalen Adapters wird die Option ausdrücklich abgelehnt. | T058 |
| HG-029 | scenario_evidence_verified | Ein bewusst integrierter curl-multi-Streamingadapter MUSS denselben Guard und dieselbe Pinning-Invariante verwenden und Cancellation erhalten. | T059, T060 |
| HG-030 | scenario_evidence_verified | Bei Cancellation oder Fehlern MÜSSEN Handler, Sockets und Response-Buffer freigegeben werden; keine hängenden Hintergrundtransfers. | T060, T061 |
| HG-031 | scenario_evidence_verified | Die Guard-API MUSS typisierte, stabile Ablehnungsgründe und eine Promise-/PSR-18-konforme Fehleroberfläche anbieten. | T062 |
| HG-032 | scenario_evidence_verified | Logs DÜRFEN keine Bodies, Credentials, vollständigen URLs, Querystrings oder rohe Requests enthalten. | T063, T064 |
| HG-033 | scenario_evidence_verified | Prüfmodus MUSS sichtbar als nicht schützend gekennzeichnet sein. `enforce`, `observe` und `disabled` dürfen nicht verwechselt werden. | T065, T066 |
| HG-034 | scenario_evidence_verified | Ein Diagnosekommando MUSS Konfiguration, Reihenfolge, Laufzeitfähigkeiten, Proxyzustand und Abdeckungsgrenzen ohne Probezugriffe anzeigen. | T067 |
| HG-035 | scenario_evidence_verified | Eine URL-Prüfung ohne Versand MUSS möglich sein; sie ist keine später wiederverwendbare Autorisierung. | T068 |
| HG-036 | scenario_evidence_verified | nr-vault MUSS über einen eigenen Adapter migriert werden; seine separate Factory DARF NICHT als automatisch global geschützt ausgegeben werden. | T069, T070 |
| HG-037 | scenario_evidence_verified | Vault-Integration MUSS Credential-Injection, Secret-Zugriffskontrolle, Auditing, OAuth-Tokenabruf, Timeout-, Streaming- und Cancellation-Verhalten erhalten. | T059, T060, T070, T071, T072 |
| HG-038 | scenario_evidence_verified | Bestehende flache Vault-Allowlist-Einträge DÜRFEN nicht automatisch globale oder uneingeschränkte interne Freigaben werden. | T039, T073 |
| HG-039 | scenario_evidence_verified | Sicherheitskritische Ablehnungen MÜSSEN mit einem nachweislich nicht kontaktierten Ziel getestet werden; eine Exception allein ist kein Nachweis. | T074 |
| HG-040 | scenario_evidence_verified | Gleichzeitige Requests und langlebige CLI-Prozesse MÜSSEN auf Cross-Request-, Cache- und Policy-Lecks getestet werden. | T053, T054, T075 |
| HG-041 | scenario_evidence_verified | Bibliotheks-, TYPO3- und Vault-Tests MÜSSEN denselben normativen Sicherheitskorpus verwenden. | T074, T076 |
| HG-042 | scenario_evidence_verified | Unsupported-Konfigurationen MÜSSEN sichtbar fehlschlagen. Die Dokumentation DARF keine weitergehende Abdeckung als die getestete behaupten. | T003, T006, T077 |
| HG-043 | scenario_evidence_verified | Neue Defaults oder eine Entfernung des Vault-Legacyverhaltens MÜSSEN als Verhaltensänderung migriert und versioniert werden. | T038, T066, T073, T080 |
| HG-044 | partial_evidence | Ressourcenverbrauch und Zeitverhalten MÜSSEN dokumentierte Grenzen besitzen; synchrone DNS-Auflösung DARF nicht fälschlich als hart deadlinefähig dargestellt werden. | T028, T057, T078, T079, T082 |
| HG-045 | scenario_evidence_verified | Jeder Security-Fix MUSS eine reproduzierbare Regression und eine Auswirkungsprüfung auf beide Integrationen erhalten. | T076, T080 |

## Security invariants

| ID | Original invariant | HG mapping | Test mapping | Status |
|---|---|---|---|---|
| INV-01 | Keine ungeprüfte Adresse: Jeder Verbindungsversuch nutzt ausschließlich Adressen des für genau diesen Versuch validierten Plans. | HG-013, HG-014, HG-015, HG-016, HG-020, HG-026 | T022, T023, T024, T025, T026, T028, T029, T030, T031, T032, T033, T040, T053, T055, T084 | scenario_evidence_verified |
| INV-02 | Vollständige Zielbindung: Scheme, kanonischer Host, effektiver Port, Methode, Endpoint-Profil und Policyrevision werden gemeinsam bewertet. Ein Wechsel invalidiert den alten Plan. | HG-007, HG-008, HG-009, HG-017, HG-018, HG-043 | T008, T009, T010, T011, T012, T013, T034, T035, T038, T081 | scenario_evidence_verified |
| INV-03 | Fail closed: Fehlerhafte URL, fehlende verwertbare Adressen, nicht unterstützter Transport, Reihenfolgefehler oder ungültige Policy führen im Enforce-Modus vor Zielkontakt zur Ablehnung. | HG-002, HG-003, HG-008, HG-014, HG-020, HG-021, HG-022, HG-042 | T003, T006, T009, T010, T011, T012, T024, T025, T026, T032, T040, T041, T042, T043, T044, T080 | scenario_evidence_verified |
| INV-04 | Keine implizite Ausnahme: Interne Freigaben gelten nur für explizit gebundene Aufrufpfade. Eine bloße Übereinstimmung von Zielhostname und globaler Liste reicht nicht. | HG-004, HG-017, HG-018, HG-038 | T005, T027, T034, T035, T036, T037, T039, T073, T076 | scenario_evidence_verified |
| INV-05 | Jede Wiederholung ist neu: Redirects und Retries durchlaufen dieselben Invarianten; es gibt keinen einmalig ausgestellten URL-Freibrief. | HG-023, HG-024, HG-025 | T038, T045, T046, T047, T048, T049, T050, T051, T052, T081 | scenario_evidence_verified |
| INV-06 | Policykomposition verengt: Zusätzliche Einschränkungen werden geschnitten, nicht ersetzt. Harte Verbote haben Vorrang. | HG-011, HG-012, HG-017, HG-019 | T004, T017, T021, T034, T039, T083 | scenario_evidence_verified |
| INV-07 | Kein Zustandstransfer: Eine interne Freigabe, ein DNS-Pin oder eine Verbindung darf nicht auf einen anderen Request, anderen Client oder andere Policy übergehen. | HG-017, HG-018, HG-026, HG-027, HG-040 | T035, T036, T038, T053, T054, T055, T056, T057, T071, T075 | scenario_evidence_verified |
| INV-08 | Ehrliche Abdeckung: Kein geschütztes Label für nicht erfasste Clients, Observe-Modus, deaktivierten Guard oder ungeprüfte Proxy-/Stream-/Custom-Handler-Pfade. | HG-021, HG-022, HG-028, HG-033, HG-034, HG-036, HG-042 | T042, T043, T044, T058, T065, T066, T067, T068, T069, T070, T077 | scenario_evidence_verified |

## Release gates

- **G0** — passed_root_confirmed: None; initial architecture gate accepted before production implementation.
- **G1** — passed_current_declared_matrix: All 74 P0 scenarios have current component evidence. Execution scope: eight standalone PHP/major tuples, four actual Core/Guzzle tuples on PHP8.5.11, two Vault tuples on PHP8.5.10; this is not every framework/PHP cross-product or broader dependency patch range.
- **G2** — passed: All 12 targeted experiments killed; no surviving mutant.
- **G3** — passed_current_component_integrations: None within recorded actual integration tuples; exact supported/runtime limitations are in their reports.
- **G4** — passed_synthetic_diagnostics: Real operator rollout acceptance remains AP-10; all final diagnostic fixture processes pass.
- **G5** — partial_known_legacy_advisories: Minimal shared-library audits are separate from the existing Vault/historical TYPO3 SVG sanitizer advisories. Functional lower bounds and Ubuntu vendor backports do not establish blanket platform vulnerability freedom; operator dependency/runtime remediation remains required.
- **G6** — external_acceptance_required: Original spec requires security review by a different person. Cross-agent source review finds/fixes defects but does not establish this human gate.
- **AP-10** — external_acceptance_required: Real operator pilot, reviewed endpoint grants, monitoring and deployment acceptance cannot be substituted by synthetic fixtures.
