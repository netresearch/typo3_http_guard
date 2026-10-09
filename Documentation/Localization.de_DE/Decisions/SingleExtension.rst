.. _decision-single-extension:

==============================
Eine installierbare Extension
==============================

:Status: Angenommen
:Datum: 2026-10-09
:Bezug: Aktualisiert die Zwei-Paket-Verpackung aus dem ursprünglichen ADR-002

.. _decision-single-extension-context:

Anlass
======

Die Extension soll alle Runtimekomponenten und ihr vollständiges Handbuch
enthalten und sich in klassischen TYPO3-Projekten ohne Composer-Aufruf
installieren lassen. Ein zusätzlich erforderliches Library-Paket würde
diesen Installationsweg vom separaten Dependency-Management abhängig machen.
Die Trennung von Policy-/Transportcode und TYPO3-Integration bleibt für
isolierte Prüfung und Adapterentwicklung erforderlich.

.. _decision-single-extension-choice:

Entscheidung
============

Es wird eine TYPO3-Extension :literal:`netresearch/nr-http-guard` mit dem
Schlüssel :literal:`nr_http_guard` geliefert. Der Sicherheitskern liegt
unter :file:`Classes/HttpGuard/`, seine Runtime-Daten unter
:file:`Resources/Private/HttpGuard/data/`. Die öffentlichen PHP-Namespaces
und APIs bleiben erhalten. TYPO3 registriert beide Produktionsnamespaces
aus den mitgelieferten PSR-4-Metadaten.

Die Extension enthält keine zweite Vendor-Kopie. Klassische Projekte nutzen
die Dependencies des offiziellen Core-Archivs; Composer-Projekte ihre
Projekt-Locks. Die Produktionsconstraints verwenden semantische Bereiche
mit ausdrücklichen Untergrenzen und Hauptversionen. Laufzeitprüfungen
kontrollieren die echte Elternklasse, SDK-Fähigkeiten und Middleware-Struktur.
Eine Factory über das öffentliche cURL-Interface begrenzt jede Lease auf
einen nativen Versuch, unabhängig von privaten SDK-Wiederholungszählern.
Feste Core-/SDK-Fixtures bleiben reproduzierbare Teststände. Die aktuell
geprüften offiziellen Core-Archive liefern den Guzzle-8-Graph.

Das Handbuch, Installationswege, Konfigurationsfelder, APIs und
Betriebsgrenzen liegen vollständig unter :file:`Documentation/`. Zusätzliche
Spezifikationen, ausführliche Ausführungsnachweise und der optionale
Vault-Patch bleiben ein getrenntes Quell-/Nachweispaket und werden nicht als
Runtime-Voraussetzung installiert.

.. _decision-single-extension-consequences:

Folgen
======

Die Runtime-Ladepfade und Testkonfigurationen ändern sich, die Policyregeln
und APIs bleiben unverändert. Die zusätzliche klassische Dependency-Zeile
erweitert den reproduzierbaren Prüfumfang. Kompatible Patch- und Minor-Updates
innerhalb der semantischen Bereiche benötigen keine neue Extension-Version.
Echte API-Inkompatibilitäten und nicht unterstützte Hauptversionen werden
abgelehnt. Feste und frei aufgelöste native CI-Zellen prüfen Core-, Wire-
und mutationsrelevantes Verhalten. Jeder Lauf bleibt seinem Quellstand und
Dependency-Lock zugeordnet.

Das Paket gilt als GPL-2.0-or-later-Extension mit erhaltenen MIT-Hinweisen
des eingebetteten Kerns. Der interne frameworkunabhängige Aufbau erlaubt
separate Kernprüfungen, ist jedoch kein Versprechen eines zweiten
veröffentlichten Composerprodukts. Die Alpha-Version wird lokal als
importierbares ZIP geliefert; TER-Veröffentlichung und Betreiberfreigabe
bleiben gesonderte Aktionen.
