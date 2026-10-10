.. _decision-single-extension:
.. _adr-0015:

=======================================
ADR-0015: Eine installierbare Extension
=======================================

:Status: Angenommen
:Datum: 2026-10-09
:Ablösung: ADR-0002, Paket- und Veröffentlichungsvorschlag

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
höchstens einen Aufruf der delegierten Factory, unabhängig von privaten
SDK-Wiederholungszählern.
Feste Core-/SDK-Fixtures bleiben reproduzierbare Teststände. Die aktuell
geprüften offiziellen Core-Archive liefern den Guzzle-8-Graph.

Das vollständige Handbuch, die Anforderungsübersicht und angenommenen
Entscheidungen liegen unter :file:`Documentation/`; gepflegte Tests und
Build-Werkzeuge unter :file:`Tests/` und :file:`Build/`. Die nummerierten
historischen ADRs stehen weiterhin unter :ref:`adr-index`. Originale
Markdown-Spezifikationen, Rohprotokolle und das optionale fremde Vault-Overlay
bleiben in unveränderter Git-Historie und einem geprüften externen Archiv
erhalten. Sie bilden kein weiteres installierbares Paket.
Original-IDs und heutige Zuordnung stehen
unter :ref:`development-requirements`.

Die umgesetzte öffentliche Core-RequestFactory-Grenze, das kontrollierte
DNS-Wire-Backend und die einmalige SDK-Factory werden in :ref:`adr-0016`,
:ref:`adr-0017` und :ref:`adr-0018` begründet.

.. _adr-0015-alternatives:

Betrachtete Alternativen
========================

Die in :ref:`adr-0002` vorgeschlagenen separaten Bibliotheks- und
Adapterpakete benötigen ein zusätzliches Dependency-Management für
klassische TYPO3-Projekte. Die einzelne Extension erfüllt den gewünschten
TER-Installationsweg und erhält die interne Trennung zwischen Sicherheitskern
und Adapter. Eine weitere Vendor-Kopie würde Core-Dependencies duplizieren
und ihre Pflege uneindeutig machen; die Extension nutzt den installierten
Core-Graph.

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
veröffentlichten Composerprodukts. Alpha-Version **0.1.1 ist auf TER und
Packagist veröffentlicht**, mit einem importierbaren Extension-ZIP. Dies
belegt weder einen Betreiberpilot noch die Bereitstellung des historischen
nr-vault-Referenzpatches. Der Nutzer hat die Alpha-Arbeit und Merges nach
wiederholter unabhängiger Prüfung und grünen einschlägigen Checks ohne
zusätzliche menschliche Freigabe autorisiert.
