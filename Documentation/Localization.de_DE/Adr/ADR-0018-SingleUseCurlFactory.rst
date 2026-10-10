.. _adr-0018:

===========================================================
ADR-0018: Eine native cURL-Handle-Erzeugung je Transferlease
===========================================================

:Status: Angenommen
:Datum: 2026-10-10
:Bezug: Ergänzt ADR-0003, ADR-0007 und ADR-0010.

.. _adr-0018-context:

Kontext
=======

Eine neue native Verbindung darf die abschließende Policyprüfung nicht
umgehen. Ein SDK kann beim Abschluss eines fehlgeschlagenen Transfers intern
eine Wiederholung anfordern; Callbacks bei der Bodyvorbereitung können
ebenfalls erneut in die Handle-Erzeugung eintreten. Private SDK-Retryzähler
sind keine stabile Grenze für diese Versuche.

Die Transferlease aus :ref:`adr-0010` benötigt deshalb eine eigene,
unmittelbar vor der nativen Handle-Erzeugung wirksame Einmaligkeitsgrenze.
Die Entscheidung betrifft den kontrollierten Transport aus :ref:`adr-0003`
und :ref:`adr-0007`, nicht eine prozessweite Unterbindung beliebiger cURL-Nutzung.

Für die Reihenfolge von Boundary und Terminal ist zusätzlich eine prüfbare
Middleware-Inventarliste nötig. Das derzeit verwendete SDK bietet diese
Liste über die private :literal:`HandlerStack.stack`-Eigenschaft. Die
Einmalfactory entfernt diese separate SDK-Kopplung nicht.

.. _adr-0018-decision:

Entscheidung
============

:literal:`SingleUseCurlFactory` implementiert die öffentliche
:literal:`CurlFactoryInterface` und dekoriert :literal:`CurlFactory(0)`.
Vor dem ersten Delegieren setzt sie ihren Verbrauchszustand. Jede zweite
:literal:`create()`-Anforderung derselben Instanz scheitert mit
:literal:`transport_unsupported`, bevor der Delegate ein weiteres Handle
erzeugt. Das gilt auch nach gescheiterter oder erneut eintretender
Bodyvorbereitung. :literal:`release()` delegiert das Freigeben, setzt die
verbrauchte Instanz aber nicht zurück.

:literal:`TransferLease` erzeugt eine neue Einmalfactory und einen eigenen
:literal:`CurlMultiHandler` je Versuch. Verbindung und DNS-Zustand werden
nicht zwischen unabhängigen Leases übernommen; frische Verbindung,
verbotene Wiederverwendung und abgeschalteter nativer DNS-Cache ergänzen
das verbindungsgebundene Pinning. Nach DNS und Bodyvorbereitung wird die
Policy erneut geprüft, bevor Netzwerkfortschritt beginnt.

:literal:`RuntimeSupport` prüft die tatsächlich verwendeten SDK-Versionen
und öffentlichen Factory-/Handler-Verträge. Die Einmaligkeitsgrenze liest
oder verändert keine privaten SDK-Retryzähler. Eine echte neue Wiederholung
des Aufrufers benötigt eine neue Policyprüfung und eine neue Lease;
Policyfehler legitimieren keinen automatischen Retry.

:literal:`RuntimeSupport::assertTransportContracts()` prüft zusätzlich die
Form der privaten :literal:`HandlerStack.stack`-Inventarliste mit einem
einzelnen bekannten Middlewareeintrag. :literal:`GuardedClientFactory` liest
das geprüfte Inventar über Reflection, um Boundary-/Terminalposition und
Einmaligkeit zu kontrollieren. Unbekannte Inventarformen werden abgelehnt;
die Kopplung bleibt ausdrücklich Teil der SDK-Kompatibilitätsgrenze.

.. _adr-0018-alternatives:

Verworfene Alternativen
=======================

* **Privaten SDK-Retryzähler setzen:** hängt von internen SDK-Details ab.
* **Nur Middleware vor dem ersten Send:** begrenzt versteckte weitere
  Handle-Erzeugungen innerhalb desselben terminalen Versuchs nicht.
* **Factory nach Release oder Vorbereitungsausfall wieder freigeben:** öffnet
  die verbrauchte Lease für einen weiteren nativen Versuch.
* **Mehrere Versuche mit demselben Pool oder derselben Lease erlauben:**
  überträgt eine frühere Prüfung auf einen neuen Verbindungsversuch.

.. _adr-0018-consequences:

Konsequenzen
============

Eine Lease kann den Delegate höchstens einmal zur Handle-Erzeugung aufrufen.
Das ist eine konkrete Versuchsschranke, keine Zusage über die Anzahl einzelner
Netzwerkpakete oder die von cURL innerhalb eines Handles geprüften Pins.
Callback- und SDK-Retryfehler bleiben kontrollierte Fehler. Cleanup und
Cancellation gehören weiter zur Lease; unabhängige Transfers tragen eigene
Ressourcen und verzichten auf Verbindungswiederverwendung.

Kompatible SDK-Patches innerhalb der unterstützten Semantikbereiche brauchen
kein Extension-Release allein wegen ihrer Versionsnummer. Eine geänderte
öffentliche Schnittstelle oder ein nicht unterstützter Majorgraph wird vor
dem geschützten Transfer abgelehnt und erfordert erneute Qualifikation.
Auch eine Änderung der privaten Stack-Inventarform kann die geschützte
Integration stoppen. Das Projekt muss diese begrenzte Reflection-Kopplung
weiter mit realen SDK-Versionen qualifizieren.

.. _adr-0018-verification:

Quellen und Nachweis
====================

Die Umsetzung steht in
:file:`Classes/HttpGuard/Transport/SingleUseCurlFactory.php`,
:file:`Classes/HttpGuard/Transport/TransferLease.php` und
:file:`Classes/HttpGuard/Transport/RuntimeSupport.php`. Die Stack-Inventarprüfung
steht zusätzlich in :file:`Classes/HttpGuard/Client/GuardedClientFactory.php`.

:file:`Tests/HttpGuard/Unit/Transport/SingleUseCurlFactoryTest.php` prüft
Create/Release, Vorbereitungsausfall und Wiedereintritt. Der Vergleich mit
Guzzles echtem öffentlichem :literal:`CurlFactory::finish()`-Pfad zeigt dort
eine zweite Handle-Erzeugung ohne Schranke und die Ablehnung mit Schranke.
Dieser Test erzeugt Handles, führt aber keinen Handler-Tick und kein
Netzwerk-I/O aus. Die getrennten Native-/Kontaktbelege sind unter
:ref:`verification-report` dokumentiert; die heutige Quellenprüfung dieses
ADRs ist kein neuer Testlauf und kein neuer Transportbeleg.

.. _adr-0018-reassessment-trigger:

Anlass für Neubewertung
=======================

Änderungen der Factory-/Handler-Verträge oder privaten Inventarform,
alternative Retrypfade,
Verbindungspooling oder neue Transportschnittstellen verlangen erneute
Einmaligkeits-, Lifecycle- und echte Kontaktprüfungen. Eine behauptete
Kompatibilität aus Composer-Auflösung allein reicht dafür nicht aus.
