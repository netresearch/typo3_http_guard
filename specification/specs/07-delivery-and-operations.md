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
