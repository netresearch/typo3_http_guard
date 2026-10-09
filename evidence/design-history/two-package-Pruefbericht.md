# Prüfbericht und Freigabestand

Die lokale Lieferung setzt die gemeinsame Policybibliothek, den isolierten cURL-Transport, die TYPO3-Extension, Diagnosen und den optionalen nr-vault-Adapter um. Ausgangspunkt sind die unveränderten acht Spezifikationen und vierzehn ADRs vom 8. Oktober 2026. Die [Zuordnung](../verification/requirements-and-tests.md) enthält alle 45 Anforderungen HG-001 bis HG-045 und alle 84 Testfälle T001 bis T084. Sie unterscheidet lokale Nachweise von den noch ausstehenden Freigabeschritten.

## Ausgeführte Prüfungen

| Bereich | Nachweis |
|---|---|
| Architekturprobe vor der Implementierung | Vier echte Core-/Guzzle-Kombinationen, zwölf Läufe mit 148 Assertions; [G0-Bericht](../evidence/architecture-probe/G0-report.md) |
| Gemeinsame Policy | Je Guzzle-Major 41 Tests mit 1.018 Assertions nach der portablen DNS-Testanpassung, versionierter Korpus mit 351 Fällen; [portable Prüfläufe](../evidence/dns-transport/) und ursprüngliche Nachweise im Paket unter `data/security-corpus/evidence` |
| Kontrollierter Transport | Je Major 34 Tests mit 865 Assertions; tatsächliche HTTP-/TLS-/mTLS-/IPv6-Verbindungen, Ablehnungen ohne native Konstruktion und ohne neue Zielkontakte, Cancellation, Redirects, Retry, Parallelität und 4-MiB-Sink |
| DNS und Fähigkeiten | Je Major neun Tests mit 189 Assertions; zusätzlich tatsächlicher Versand ohne ext-curl mit nativer Konstruktion 0 und Zielkontakt 0; [Nachweise](../evidence/dns-transport/) |
| Absichtlich eingebaute Schutzfehler | Alle sechs Fehler je Guzzle-Major erkannt, zwölf von zwölf; die Zeugen speichern native Versuche und tatsächliche TCP-/HTTP-Zielkontakte; [Mutationen](../evidence/transport/mutations/summary.json) |
| TYPO3 | 168 Prozesse mit 140 Wire-Assertions auf Core 13.4.35/14.3.7 × Guzzle 7.15.5/8.2.0; 152 CLI-Aufrufe und vier Konfliktstarts ohne neue Zielkontakte; [Core-Bericht](../evidence/typo3-integration/README.md) |
| PHP-Kompatibilität | Alle acht Kombinationen aus PHP 8.2.33/8.3.33/8.4.25/8.5.10 × Guzzle 7/8: jeweils 122 Tests und 2.167 Assertions, keine Fehler oder übersprungenen Tests; eingefrorene Images und Lockdateien im [Matrixbericht](../verification/evidence/library-matrix/README.md) |
| Vault | Beide Majors: vollständige Unit-Suite 3.978/14.868; Functional Guzzle 7: 496/2.653, Guzzle 8: 496/2.654, ohne übersprungene Tests oder PHP-Deprecations. Die 19 Adapterfälle prüfen tatsächliche Resource-/Token-/Streaming-/Cancellation-Pfade. Zusätzlich bestehende Fuzz-Suite 1.612/7.146; jeweilige Laufzeiten und Quellstände im [Vault-Bericht](../integrations/nr-vault/README.md) |
| Statische Prüfung | Bibliothek PHPStan Level 8 ohne Baseline; Extension Level 8; Vault nach dessen strikten Projektregeln einschließlich API-Snapshot, Architektur, Rector und Formatierung |
| Zusätzliches Review | Agentenreviews haben Fehler in Policy-Retry, Middlewareposition, URL-Vorverarbeitung und Diagnosefällen aufgedeckt; [Prüfnotiz](../evidence/reviews/independent-agent-review.md) |

Bei synthetischen Ablehnungen zählen die Tests native Handler sowie TCP-Accepts und HTTP-Requests am getrennten Zielserver. Ein erwarteter Fehler allein reicht nicht als Beleg. Die aufgezeichneten Kontakte gehören ausschließlich zu Testcontainern. Rohdaten aus Produktivrequests oder echte Zugangsdaten sind nicht enthalten.

Der gemeinsame Fall `EP-PRIVATE-UNBOUND` wird über seine tatsächliche Korpus-ID und seinen Inhaltshash eingebunden. Bibliothek, Core und Vault müssen damit denselben verweigerten privaten Zugriff zeigen. Legacy-Hostlisten erzeugen keine neuen Grants. Beobachtung und deaktivierter Guard behalten ihre bestehenden Transportpfade; sie werden als ungeschützt ausgewiesen.

## Grenzen der Messung

Die reine Policybewertung mit 64 IPv6-Adressen und 128 Profilen wurde auf diesem WSL-Host über 200 Stichproben gemessen: p95 1,455 ms ohne DNS, Netzwerk und Logging. Die Messung liegt unter dem Zielwert von 2 ms, benennt aber kein ausgeführtes CI-Referenzsystem. Dieser Teil von T079 bleibt daher als ausstehende Abnahme gekennzeichnet. Der reale 4-MiB-Sink-Test weist getrennt nach, dass der Guard keinen zusätzlichen Bodybuffer anlegt.

Der DNS-Backend-Timeout wird unabhängig vom HTTP-Timeout begrenzt. Ein injizierter UDP-Blackhole-Fall misst die eingestellte Deadline. Eigene Resolver werden nicht als beliebig unterbrechbar dargestellt. Konfigurationsänderungen erfordern neue Snapshots, Caches und kontrollierten Workerersatz; es gibt keine sofortige Hot-Revocation bereits laufender Transfers.

## Release-Gates aus dem Plan

| Gate | Lokaler Stand und verbleibende Voraussetzung |
|---|---|
| G0 Architekturprobe | Erfüllt und vor der Produktionsimplementierung bestätigt |
| G1 Sicherheitskorpus | Alle 74 P0-Szenarien sind mit ausgeführten Nachweisen zugeordnet; die acht Bibliothekskombinationen und tatsächlichen Core-/Vault-Pfade enthalten keine übersprungenen Fälle. Die Leistungsabnahme auf einem benannten CI-Referenzsystem gehört zum noch offenen P1-Teil von T079 |
| G2 Mutationen | Erfüllt: zwölf ausgewählte Tests werden durch die sechs gezielten Fehler je Major rot; tatsächliche Kontakte sind aufgezeichnet |
| G3 Integration | Echte Core-Pfade sowie ausdrücklich integrierter Vault-Adapter geprüft; kein Anspruch auf automatische Abdeckung anderer HTTP-Clients |
| G4 Betrieb | Offline-Diagnose, redigierte Ereignisse, Moduswechsel und bestehende Vault-Kontrollen geprüft; Rollback im konkreten Betreiberprojekt steht noch aus |
| G5 Abhängigkeiten | Exakte Transport-Tupel und Betriebssystemstände dokumentiert; die drei bestehenden SVG-Sanitizer-Advisories verhindern eine allgemeine Freigabe der historischen Framework-Auflösung |
| G6 Unabhängige Person | Offen. Ein Agentenreview ersetzt die ausdrücklich verlangte Prüfung durch eine andere Person nicht |

AP-01 bis AP-08 sind als lokale Implementierung und synthetische Integrationsprüfung geliefert. AP-09 verlangt zusätzlich die unabhängige menschliche Sicherheitsprüfung. Für AP-10 fehlen bislang eine konkrete Betreiber-Testinstanz und die freizugebenden internen Endpoints. Diese Schritte werden nicht durch lokale Testergebnisse als erledigt ausgewiesen.

Die Lieferung wurde weder veröffentlicht noch in ein Betreiberprojekt deployt. [Installation und Betrieb](Installation-und-Betrieb.md) beschreibt die Einführung, die erfassten Aufrufpfade und den bewussten Rollback. [Abhängigkeiten](Abhaengigkeiten.md) enthält die Auditbefunde und den Unterschied zwischen funktionaler libcurl-Untergrenze und Distributor-Sicherheitskorrekturen.
