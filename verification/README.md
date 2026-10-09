# Prüfungen der einen TYPO3-Extension reproduzieren

Die Produktion besteht aus dem Root-Paket `netresearch/nr-http-guard`.
`Classes/HttpGuard/` und `Resources/Private/HttpGuard/data/` sind Teil dieser
Extension. Die minimalen Composer-Projekte in `dependencies/combined-kernel/`
sind Testfixtures für die drei exakten SDK-Kombinationen und keine separat
zu installierenden HTTP-Guard-Produkte.

## Kernelmatrix

Die tatsächliche Zwölf-Zellen-Matrix ist in
[evidence/extension-matrix/README.md](evidence/extension-matrix/README.md)
festgehalten. Jede Kombination aus vier PHP-Versionen und drei exakten SDK-Tupeln
führt 126 Tests mit 2.180 Assertions ohne übersprungene Fälle aus. Für die
Reproduktion werden Docker, Composer, Bash, Python, rsync und OpenSSL benötigt.
Abhängigkeiten werden in einem nativen Linux-Verzeichnis installiert.

```sh
bash verification/scripts/run-library-matrix.sh \
  /absolute/path/to/nr_http_guard \
  /tmp/nr-http-guard-kernel-matrix \
  /tmp/nr-http-guard-kernel-evidence
```

Der Skriptname ist aus der früheren Paketstruktur erhalten. Das Skript kopiert
heute den Kern aus der einen Extension, deren Produktionsmanifest und die
zugehörigen Tests in isolierte Prüfruntimes. Es verändert keine Produktionsquelle.
Alle PHP-Images sind mit Digest festgehalten. Die Loopback- und IPv6-Prüfungen
benötigen die dokumentierte native Linux-Netzwerkumgebung.

`Tests/HttpGuard/Integration/prepare-wire.sh` betreibt ausschließlich eigene
synthetische öffentliche/private/IPv6-Testziele. Vor und nach jedem relevanten
Fall werden native Konstruktion, TCP-Accepts und HTTP-Requests gezählt. Ein
Fehler allein belegt keine unterbliebene Verbindung. Gleichzeitige Prüfungen
auf denselben Zählern verfälschen den Nachweis und sind zu vermeiden.

## Gezielte Schutzfehler

Alle 18 Mutanten auf dem zusammengeführten Quellstand sind in
[evidence/extension-mutations/README.md](evidence/extension-mutations/README.md)
aufgezeichnet. Die sechs Fehler werden für alle drei SDK-Tupel ausschließlich
in disponiblen Kopien durch das AST-Werkzeug erzeugt. Ein erkannter Mutant muss
einen fehlgeschlagenen Test sowie einen tatsächlichen zusätzlichen HTTP-Kontakt
und einen nativen Versuch oder einen umgangenen Leaf-Aufruf nachweisen.

```sh
python3 verification/scripts/run-mutations.py \
  --package /absolute/path/to/nr_http_guard \
  --scratch /tmp/nr-http-guard-mutations \
  --evidence /tmp/nr-http-guard-mutation-evidence \
  --g7-vendor /tmp/nr-http-guard-kernel-matrix/guzzle7/vendor \
  --g8-vendor /tmp/nr-http-guard-kernel-matrix/guzzle8/vendor \
  --classic7-vendor /tmp/nr-http-guard-kernel-matrix/guzzle7ter/vendor \
  --editor /absolute/path/to/php-ast-edit \
  --image ghcr.io/typo3/core-testing-php85@sha256:53df750b7e68ccce8da03a03bfe7cb57d732308b932f2ea92b1fd31679626bc6
```

## Tatsächliche TYPO3- und Vault-Pfade

`Build/Fixtures/README.md` beschreibt die Composer- und klassischen Core-Fixtures.
Die klassischen Fixtures verwenden die offiziellen Gesamt-Archive, den echten
ExtensionManager-Aktivierungsweg und Core-generierte Klassenladeinformationen.
Sie laden die Sicherheitsklassen aus der entpackten Extension; ein separat
installiertes HTTP-Guard-Librarypaket ist nicht verfügbar.

Die optionalen Änderungen unter `integrations/nr-vault/` gehören zum anderen
Produkt nr-vault. Ihr Patch wird ausschließlich auf dem genannten Ausgangscommit
nach einer erfolgreichen `git apply --check` angewendet. Seine Adapter-Smokes
binden die Klassen der zusammengeführten Extension ein; die ausführlichen alten
Unit/Fuzz/Functional-Nachweise behalten ihren ursprünglichen Quellstand.

Die originale Acht-Zellen-Matrix und zwölf früheren Mutanten bleiben historische
Nachweise. `evidence/packaging/source-layout-map.json` im Repository erklärt ihre
alten Pfade. Der Freigabe-Ledger trennt lokale Ausführung, aktuelle Quellbindung
und noch ausstehendes menschliches Review, Betreiberpilot und CI-Leistungsabnahme.
