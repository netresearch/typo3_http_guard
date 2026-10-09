# Prüfbericht der zusammengeführten TYPO3-Extension

Stand: 9. Oktober 2026. Die aktuelle Lieferung ist **eine** TYPO3-Extension
`netresearch/nr-http-guard` mit enthaltenem Sicherheitskern und vollständigem
Handbuch. Ein zusätzliches HTTP-Guard-Librarypaket wird nicht benötigt.
Der ausdrückliche Nutzerwunsch ersetzt den Paketvorschlag aus ADR-0002;
[Entscheidung](../evidence/packaging/single-package-decision.md) und
[Pfadabbildung](../evidence/packaging/source-layout-map.json) dokumentieren dies.
Die ursprünglichen acht Spezifikationen und vierzehn ADRs bleiben unverändert.

## Aktuelle ausgeführte Nachweise

| Bereich | Nachweis auf dem zusammengeführten Quellstand |
|---|---|
| Kernelmatrix | Alle zwölf Kombinationen aus PHP 8.2.33/8.3.33/8.4.25/8.5.10 und drei exakten SDK-Tupeln: jeweils **126 Tests, 2.180 Assertions**, keine Skips/Fehler/Failures. Die dritte Kombination 7.15.3/2.5.2/2.13.0 entspricht den offiziellen klassischen Core-Archiven. [Matrix](../verification/evidence/extension-matrix/README.md) |
| Gezielte Schutzfehler | Alle **18** Mutanten erkannt: sechs gezielte Fehler für jeden der drei SDK-Tupel. Der Nachweis verlangt Testfehler, zusätzlichen tatsächlichen HTTP-Kontakt und nativen Versuch oder umgangenen Leaf. Root hat alle Zeugen unabhängig nachgerechnet. [Mutationen](../verification/evidence/extension-mutations/README.md) |
| TYPO3 mit Composer | Vier echte Core-/SDK-Instanzen: **168 Prozesse, 140 Wire-Assertions**, 156 Offline-Nachweise ohne zusätzliche TCP-/HTTP-Kontakte. Eine Extension liefert beide Namespaces; kein separates Librarypaket ist installiert. Neue Locks und Prüfläufe liegen unter `evidence/packaging/` |
| Klassisches TYPO3 | Offizielle Core-Archive **13.4.35 und 14.3.7**, echte ExtensionManager-Aktivierung, SQLite-Projektsetup, persistierte PackageStates und Core-generierte Klassenladeinformationen. **84 Prozesse, 70 Wire-Assertions**, 78 Offline-Nachweise ohne zusätzliche TCP-/HTTP-Kontakte. Die Klassen stammen physisch aus dem ZIP-Installationsverzeichnis |
| Aktuelles Extension-ZIP | Finales ZIP mit 106 Dateien in beiden klassischen Installationen erneut eingespielt: jeweils 14 Klassen-/Quellprüfungen und 35 tatsächliche Core-Wire-Assertions bestanden; die zusätzlichen 70 Wire-Assertions werden getrennt von der 252-Prozess-Matrix festgehalten. Keine zusätzliche Composer-Installation, keine Quell-Symlinks, kein privater SDK-Vendor im Extension-ZIP. Das finale Archiv enthält genau ein Produktionsmanifest, Kernklassen, eigene Policydaten, Lizenzen und Handbuch. [Paketnachweise](../evidence/packaging/README.md) |
| Gemeinsame Unit-Suite | **96 Tests / 1.173 Assertions** auf tatsächlichen Core14-/G7- und Core14-/G8-Abhängigkeiten; Root-PHPUnit lädt Adapter und enthaltenen Kern |
| Statische Prüfung | Kernel PHPStan Level 8 ohne Baseline/Fehler; tatsächliche Core14-Anbindung Level 8 ohne Fehler. Die nur zur Analyse nötige Core13-Deklaration liegt unter Build und gehört nicht zur Produktion oder zum ZIP |
| nr-vault | Aktuelle Adapter-Smokes: G7 **19/249**, G8 **19/250**; Guard-Unit **9/50**, API-Snapshot **1/247**, PHPStan ohne Fehler, Rector sauber, CGL ohne Änderungen an 595 Dateien. Der 25-Dateien-Patch wurde sauber auf dem festgehaltenen Ausgangscommit angewendet; alle Overlay-Hashes stimmen. [Vault-Nachweise](../integrations/nr-vault/EVIDENCE.md) |
| Dokumentation | Vollständiges Handbuch direkt im Paket: **13 RST-Seiten, drei PHP-Beispiele**, guides.xml und Lizenzhinweise. Offizieller TYPO3-Renderer ohne Warnungen. Die Beispiele wurden gegen tatsächliche GuardConfig, Interfaces und PolicyEngine geprüft; vier unzulässige Eingaben werden abgelehnt, kein Ziel-HTTP. [Dokumentationsbericht](../evidence/packaging/documentation-report.json) |

Die neuen Kernel-Zellmanifeste wurden zusätzlich unabhängig mit den aktuellen
Quelldateien verglichen: **3.192 Einträge, keine Abweichungen**. Nicht nur
Exceptions, sondern native Konstruktion, TCP-Accepts und HTTP-Requests an
getrennten eigenen Testzielen belegen erlaubten Versand und verweigerte Kontakte.
Echte Zugangsdaten oder Produktivrequests gehören nicht zu diesen Prüfungen.

## Historische Nachweise

Die Architekturprobe G0 wurde vor der ursprünglichen Implementierung auf vier
Core-/Guzzle-Kombinationen ausgeführt: zwölf Läufe mit 148 Assertions. Sie bleibt
als ursprünglicher Architekturbeleg erhalten.

Die frühere Acht-Zellen-Kernelmatrix mit jeweils 122/2.167 und die zwölf damaligen
Mutanten gehören zur vorherigen Zwei-Paket-Struktur. Die früheren vollständigen
Vault-Suiten (Unit 3.978/14.868, Functional 496/2.653 beziehungsweise 496/2.654 und
Fuzz 1.612/7.146) behalten ihren jeweiligen aufgezeichneten Quellstand. Sie werden
nicht als erneute vollständige Ausführung des umgepackten Standes ausgegeben.
Die aktuellen Vault-Smokes und dessen unveränderte Produktions-PHP-Diffs prüfen
gezielt den neuen Paket- und Fixturezuschnitt.

Unveränderte historische Laufmanifeste behalten ihre damaligen Pfade und Hashes.
Die Pfadabbildung stellt den Bezug zum heutigen Layout her. Der
[Anforderungsledger](../verification/requirements-and-tests.md) erhält alle
45 ursprünglichen Anforderungen, 84 Szenarien und acht Invarianten; die Grenze
zwischen frameworkunabhängigem Kern und installierbarem Paket ist dort erklärt.

## Grenzen und Freigabe

Die Mikrobenchmark auf dem früher aufgezeichneten WSL-Host ergab über 200
Stichproben mit 64 IPv6-Adressen und 128 Profilen p95 1,455 ms ohne DNS, Netzwerk
und Logging. Dies ist weiterhin keine Messung auf dem benannten CI-Referenzsystem;
dieser Teil von T079 bleibt offen. Die neuen Transportläufe enthalten getrennt
den tatsächlichen 4-MiB-Sink-Test ohne zusätzlichen Guard-Bodybuffer.

Die Prüfung qualifiziert die aufgezeichneten Linux-/PHP-/SDK-Tupel und tatsächlichen
Core-Pfade. Sie qualifiziert nicht jeden Framework-/PHP-Crossproduct, alle
Hostingumgebungen oder zukünftige Patchstände. Unbekannte und gemischte SDK-Tupel
bleiben vor dem nativen Versand gesperrt. Frühe Bootstrap-Anfragen, fremde SDKs,
Request-Handler-Ersatz vor Middlewareeintritt und direkte Socketaufrufe benötigen
ihre eigene Integration. Vault wird nur durch seinen ausdrücklich ausgewählten
Adapter eingebunden.

| Release-Gate | Stand |
|---|---|
| G0 Architektur | Ursprüngliche Probe erfüllt; neue Paket-/Core-Klassenladepfade zusätzlich tatsächlich geprüft |
| G1 Sicherheitsfälle | Die 74 P0-Szenarien haben zugeordnete ausgeführte Nachweise; aktuelle zwölf Kernzellen und echte Composer-/Classic-/Vault-Pfade ergänzen diese. T079 CI-Leistung bleibt offen |
| G2 Mutationen | Aktuell 18/18 auf dem zusammengeführten Stand mit tatsächlichen Kontakten |
| G3 Integration | Tatsächliche Core-Pfade und expliziter Vault-Adapter geprüft; keine automatische Abdeckung fremder Clients |
| G4 Betrieb | Offline-Diagnose, Moduswechsel und native Registrierung geprüft; Betreiber-Rollback im konkreten Projekt steht aus |
| G5 Abhängigkeiten | Exakte SDK-Tupel dokumentiert und auditiert. Die drei bestehenden historischen SVG-Sanitizer-Advisories bleiben eine Voraussetzung für die Gesamtfreigabe |
| G6 Andere Person | Unabhängiges menschliches Review offen; Agentenreviews ersetzen diese Abnahme nicht |

Für AP-10 fehlen eine konkrete Betreiber-Testinstanz und freizugebende interne
Endpoints. Der lokale Alpha-Stand ist nicht im TER veröffentlicht und nicht in
ein Betreiberprojekt deployt. Die vollständige Betriebsanleitung gehört zur
Extension unter [Documentation](../Documentation/Index.rst). Details zu den
bestehenden Abhängigkeitsbefunden stehen unter
[Abhängigkeiten](Abhaengigkeiten.md).
