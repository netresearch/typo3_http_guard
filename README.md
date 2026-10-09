# HTTP Guard für TYPO3

HTTP Guard ist **eine TYPO3-Extension** mit dem Extension-Key `nr_http_guard` und dem Composer-Namen `netresearch/nr-http-guard`. Der Sicherheitskern ist direkt in dieser Extension enthalten. Eine zusätzliche HTTP-Guard-Bibliothek muss weder installiert noch separat versioniert werden.

Die Extension prüft ausgehende TYPO3-HTTP-Anfragen, bevor sie eine Verbindung aufbaut. Öffentliches HTTP darf keine privaten oder besonderen Netzbereiche erreichen. Freigegebene interne Dienste benötigen ausdrücklich gebundene Endpoint-Clients; eine Hostliste allein erteilt keine Freigabe.

## Installation

Für klassische TYPO3-Installationen wird das Extension-ZIP über den Extension Manager importiert und aktiviert. Die Extension enthält ihren Sicherheitskern, die notwendigen eigenen Policydateien und die vollständige Dokumentation. Die übrigen Laufzeitkomponenten stammen aus der unterstützten TYPO3-Installation. Ein Composer-Aufruf zum Nachladen des Sicherheitskerns entfällt.

Für Composer-Projekte wird dieselbe Extension als einziges Paket eingebunden. Die aktuelle lokale Lieferung ist noch nicht auf Packagist oder im TER veröffentlicht; für die lokale Prüfung wird das entpackte Verzeichnis als Composer-Path-Repository verwendet. Beide Installationswege sowie Voraussetzungen, Konfiguration und Inbetriebnahme stehen vollständig in [Documentation](Documentation/Index.rst).

## Dokumentation

- [Installation](Documentation/Installation/Index.rst)
- [Konfiguration](Documentation/Configuration/Index.rst)
- [Betrieb und Rollback](Documentation/Operations/Index.rst)
- [API und Schutzumfang](Documentation/Api/Index.rst)

Die Dokumentation gehört zum Extension-Paket. Ursprüngliche Spezifikationen, umfangreiche Messprotokolle und die optionalen nr-vault-Änderungen liegen zusätzlich im Entwicklungsrepository; sie werden zum Installieren und Betreiben der Extension nicht benötigt.

## Aufbau und Entwicklung

`Classes/` enthält die TYPO3-Anbindung, `Classes/HttpGuard/` ihren internen Sicherheitskern und `Resources/Private/HttpGuard/data/` die zugehörigen Policydaten. Die bestehenden PHP-Namensräume bleiben kompatibel; beide werden von dieser einen Extension bereitgestellt. Es gibt keine eigenständige Produktionsbibliothek und keine zweite HTTP-Guard-Paketversion.

Die Testwerkzeuge unter `Build/` und `verification/` prüfen die Extension und ihren Kern. Minimale Composer-Manifeste in Testfixtures installieren ausschließlich Prüfabhängigkeiten; sie sind keine zusätzlich zu installierenden Produktionspakete. Die ursprünglichen Nachweise der früheren Paketstruktur bleiben als historische Nachweise erhalten.

## Stand und Freigabe

Dies ist ein lokaler Alpha-Stand zur Prüfung. Veröffentlichung im TER, eine menschliche Sicherheitsprüfung und der Pilot an einer Betreiberinstanz stehen aus. Die [Installationsanleitung](Documentation/Installation/Index.rst) benennt die geprüften Laufzeitkombinationen; das [Betriebshandbuch](Documentation/Operations/Index.rst) beschreibt die verbleibenden Freigabeschritte und Einschränkungen. Eine Installation aktiviert keine automatischen internen Freigaben.

Die Extension steht unter GPL-2.0-or-later; die ursprünglichen MIT-Hinweise ihres enthaltenen Sicherheitskerns bleiben erhalten. Siehe [Lizenzhinweise](LICENSES.md).
