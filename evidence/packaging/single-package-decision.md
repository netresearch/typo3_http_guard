# Eine TYPO3-Extension mit enthaltenem Sicherheitskern

Die ausdrückliche Nutzerentscheidung vom 9. Oktober 2026 ersetzt den
ursprünglichen Paketvorschlag aus ADR-0002: HTTP Guard wird als ein Paket
`netresearch/nr-http-guard` mit dem Extension-Key `nr_http_guard` geliefert.
Der Grund ist eine gemeinsame Verwaltung und die Installation über den TER
auch ohne Composer-Aufruf durch den Betreiber.

Die fachlichen Grenzen bleiben erhalten: Der interne Sicherheitskern kennt
keine TYPO3-Konfiguration und keine Vault-Secrets. Die TYPO3-Anbindung und der
optionale Vault-Adapter verwenden dieselben Klassen. Zwei PHP-Namensräume
bezeichnen dabei Codebereiche innerhalb einer Extension, keine zwei Pakete.
Es werden keine zwei Produktionsmanifeste, keine Paket-Aliase und kein
ersetzendes Fake-Bibliothekspaket benötigt.

Der klassische Core lädt beide Namensräume über seine eigenen
Klassenladeinformationen aus dem Extension-Manifest. Die Extension bringt
ihre eigenen Kernklassen und Policydateien mit. Die bereits vom unterstützten
TYPO3-Core bereitgestellten HTTP-/PSR-Abhängigkeiten werden nicht zusätzlich
kopiert oder überschrieben.

Die offiziellen klassischen Core-Distributionen 13.4.35 und 14.3.7 enthalten
eine weitere exakte SDK-Kombination (Guzzle 7.15.3, Promises 2.5.2 und PSR-7
2.13.0). Ihre Aufnahme bedarf eines Quellvergleichs und ausgeführter Kernel-
und Core-Prüfungen; eine erfolgreiche ZIP-Erstellung allein reicht nicht.
Unbekannte oder gemischte Kombinationen bleiben gesperrt.

Installation, Konfiguration, API, Schutzumfang und Betrieb stehen vollständig
unter `Documentation/` und werden mit dem Extension-ZIP ausgeliefert. Das
Entwicklungsrepository bewahrt zusätzlich ursprüngliche Entwürfe, historische
Prüfnachweise und optionale Vault-Änderungen. Diese zusätzlichen Unterlagen
werden für die Installation der Extension nicht benötigt.

Die Zusammenführung erteilt keine Produktivfreigabe und keine Erlaubnis zur
Veröffentlichung im TER. Menschliches Review, Betreiberpilot, bestehende
Framework-Abhängigkeitsbefunde und die CI-Leistungsabnahme bleiben eigene
Freigabebedingungen.
