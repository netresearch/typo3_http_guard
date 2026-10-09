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
qualifizierten Locks. Der Runtimevertrag prüft weiterhin exakte vollständige
SDK-Tuples. Das in beiden geprüften offiziellen Core-Archiven enthaltene
Tuple 7.15.3 / 2.5.2 / 2.13.0 wird zusätzlich gezielt qualifiziert, nachdem
der bisherige Stand es erwartungsgemäß gesperrt hat.

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
erweitert ausschließlich das exakte qualifizierte Tuple; ungeprüfte
Patchversionen und gemischte Versionen bleiben gesperrt. Die PHP-/SDK-Matrix
wird mit dem zusammengeführten Quellstand erneut ausgeführt, ebenso echte
Core-Archive, Wire- und Mutationstests.

Das Paket gilt als GPL-2.0-or-later-Extension mit erhaltenen MIT-Hinweisen
des eingebetteten Kerns. Der interne frameworkunabhängige Aufbau erlaubt
separate Kernprüfungen, ist jedoch kein Versprechen eines zweiten
veröffentlichten Composerprodukts. Die Alpha-Version wird lokal als
importierbares ZIP geliefert; TER-Veröffentlichung und Betreiberfreigabe
bleiben gesonderte Aktionen.
