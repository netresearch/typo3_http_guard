# Abhängigkeiten und Sicherheitsstand

Stand der Prüfung: 9. Oktober 2026. Dieser Bericht bindet die lokalen Ergebnisse an aufgezeichnete Paketstände. Er ist keine dauerhafte Freigabe für spätere Builds oder Updates.

## Composer

Der direkt in der Extension enthaltene Sicherheitskern akzeptiert drei vollständige, exakte Kombinationen: Guzzle 7.15.3 / Promises 2.5.2 / PSR-7 2.13.0 aus den offiziellen klassischen Core-Archiven, Guzzle 7.15.5 / Promises 2.5.3 / PSR-7 2.13.1 oder Guzzle 8.2.0 / Promises 3.0.2 / PSR-7 3.1.0. Es gibt kein zusätzliches Produktionspaket für diesen Kern. Zusätzlich zu den Composer-Grenzen verweigert die Laufzeit unbekannte oder gemischte Kombinationen vor dem nativen Versand. Die minimalen Test-Lockdateien werden mit PHP-8.2-Plattformanforderungen aufgelöst; höhere PHP-Versionen ersetzen keine Prüfung der Untergrenze.

Die TYPO3-Extension begrenzt Core auf 13.4.35 oder 14.3.7. Diese beiden Core-Versionen sind in vier Composer-Bootstrap-/RequestFactory-Instanzen und in ihren offiziellen klassischen Gesamt-Distributionen geprüft. Die Zuordnung berücksichtigt, dass die Gesamt-Distribution im InstalledVersions-Katalog `typo3/cms` statt `typo3/cms-core` meldet; die Extension ermittelt den Core-Stand deshalb über die tatsächliche TYPO3-Version. Der Vault-Patch gilt für den in seinem Manifest genannten Ausgangscommit und fügt keine verpflichtende Guard-Abhängigkeit zum bestehenden Projekt hinzu.

Die bestehende TYPO3-/Vault-Testauflösung enthält `enshrined/svg-sanitize` 0.22.0 mit drei am 8. Oktober 2026 veröffentlichten Auditbefunden: CVE-2026-107379, CVE-2026-107380 und CVE-2026-107381. Der vollständige Auditbefund enthält außerdem das aufgegebene Entwicklungswerkzeug `symplify/rule-doc-generator-contracts`. Diese Befunde sind keine neu hinzugefügten Guard-Abhängigkeiten. Der Auditbericht bleibt Bestandteil der Lieferung; eine Produktionsfreigabe der betroffenen Gesamtauflösung steht aus.

Für die isolierten Core-Testinstanzen wurden ausschließlich die drei konkreten SVG-Advisory-IDs bei der Installation ausgenommen. Diese Instanzen verarbeiten keine SVGs. Die Produktionsmanifeste der Lieferung enthalten keine entsprechenden Ausnahmen. Die Ausnahme ermöglicht die Integrationstests und ist kein Nachweis einer sicheren SVG-Verarbeitung. Vor einem Deployment muss die betroffene Framework-Abhängigkeit behoben oder durch die zuständige menschliche Prüfung anhand des tatsächlichen Einsatzes bewertet werden. Quellen: [DTD-Absturz](https://github.com/advisories/GHSA-v383-3rw5-q8rf), [Stored XSS](https://github.com/advisories/GHSA-9rjx-3jch-6vjf), [Resolver-DoS](https://github.com/advisories/GHSA-m9xh-6747-9r6f).

## libcurl und Betriebssystem

Für das Mehradressen-Pinning gilt funktional libcurl 7.59.0 als Untergrenze. Sie wurde aus der [offiziellen CURLOPT_RESOLVE-Dokumentation](https://curl.se/libcurl/c/CURLOPT_RESOLVE.html) abgeleitet. Die Software kann nicht allein am Versionsstring beurteilen, welche Sicherheitskorrekturen ein Distributor zurückportiert hat.

Der lokale Ubuntu-24.04-Lauf verwendet libcurl 8.5.0 aus dem Paketstand `8.5.0-2ubuntu10.15`. Dieser Stand entspricht der für Noble in [USN-8820-1](https://ubuntu.com/security/notices/USN-8820-1) genannten Korrektur. Der Notice wurde am 24. September 2026 veröffentlicht; Paket- und Changelog-Aufzeichnungen liegen unter `evidence/runtime-security`. Das belegt diesen Paketstand und ersetzt keinen späteren Abgleich aller Herstellerhinweise.

Die aufgezeichneten PHP-Testimages enthalten einen anderen Build: Alpine 3.24.2 mit libcurl 8.22.0 und dem Paketstand 8.22.0-r0. Die [curl-Seite für 8.22.0](https://curl.se/docs/vuln-8.22.0.html) nennt zum Prüfzeitpunkt keine veröffentlichten Schwachstellen; die [versionsbezogene JSON-Datei](https://curl.se/docs/vuln-8.22.0.json) ist als Original gespeichert. Daraus folgt keine allgemeine Aussage über andere libcurl-, OpenSSL- oder Betriebssystembuilds. Die Images sind mit Digest und tatsächlich ausgeführten PHP-/OS-/curl-Daten festgehalten.

## Updates

Ein Update der Transportabhängigkeiten verlangt eine neue Quellcodeprüfung der Handler, Optionen, Redirects, Proxyumgebung und versteckten Retrypfade. Danach werden der gesamte anwendbare P0-Korpus, die realen TYPO3-/Vault-Pfade und die sechs gezielten Mutationen je Guzzle-Major wiederholt. Erst dann werden Manifest und Laufzeit-Tupel erweitert. Eine neue Composer-Auflösung allein ist keine Freigabe.

Neue Adressdaten werden bei Entwicklung und Release aus den dokumentierten Primärquellen aktualisiert, geprüft und als versionierter Korpus ausgeliefert. Im Requestpfad werden keine Sicherheitslisten aus dem Internet nachgeladen.

Die neue Zwölf-Zellen-Kernelmatrix und die drei minimalen, geprüften Testfixture-Lockdateien liegen unter `verification/evidence/extension-matrix/` und `verification/dependencies/combined-kernel/`. Diese Prüfabsicherungen installieren keine zweite HTTP-Guard-Produktionsbibliothek. Die betriebliche Anleitung zu diesen Grenzen wird direkt mit der Extension unter `Documentation/` ausgeliefert.
