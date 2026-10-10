.. _adr-0016:

========================================================
ADR-0016: Die öffentliche RequestFactory als URI-Grenze
========================================================

:Status: Angenommen
:Datum: 2026-10-10
:Bezug: Ergänzt ADR-0003; dessen Middleware- und Transportgrenzen bleiben.

.. _adr-0016-context:

Kontext
=======

Ein PSR-7-URI-Objekt kann Informationen aus der ursprünglichen Zeichenfolge
bereits verloren haben. Eine Prüfung erst in der Middleware kann solche
Eingaben nicht rückwirkend unterscheiden. Der registrierte TYPO3-Pfad muss
deshalb die rohe URI vor der Core-Verarbeitung prüfen. Die öffentliche
:literal:`TYPO3\CMS\Core\Http\RequestFactory` ist dafür der Einstieg;
die interne :literal:`GuzzleClientFactory` wird nicht ersetzt.

Core 13 und Core 14 unterscheiden sich bei :literal:`readonly`. Eine
Versionsnummer allein belegt keine passende Elternklasse. Auch eine fremde
Objektregistrierung oder unterschiedliche Factory-Instanzen würden die
nachgewiesene Grenze verändern.

.. _adr-0016-decision:

Entscheidung
============

:file:`ext_localconf.php` registriert die zur tatsächlichen Core-Form passende
:literal:`GuardedRequestFactory13` oder :literal:`GuardedRequestFactory14`,
sofern noch keine fremde Registrierung vorliegt. Die Registrierung wird
vor jedem Aufruf überprüft, auch in Disabled; ein Konflikt wird nicht
überschrieben oder still akzeptiert. Core-Factory und PSR-Factory müssen
dieselbe erwartete Instanz liefern.

:literal:`RequestFactoryCompatibility` prüft unterstützte Version,
Sichtbarkeit, Vererbbarkeit, Rückgabetyp, Parameter und Konstruktor vor dem
Laden der Ersatzklasse. Die Form mit :literal:`readonly` muss zum Core-Zweig
passen. Die öffentliche Aufrufsignatur bleibt erhalten.

:literal:`RawRequestGuardTrait` prüft die rohe URI in Enforce vor der
PSR-7-Verarbeitung. Observe meldet eine Ablehnung, delegiert aber weiterhin;
Disabled überspringt die rohe Policyprüfung. Anschließend delegiert die
Ersatzklasse an die ursprüngliche Core-Factory. Die Middlewareordnung,
Core-Kontextregeln und der kontrollierte terminale Transport aus
:ref:`adr-0003` bleiben zusätzliche Grenzen.

.. _adr-0016-alternatives:

Verworfene Alternativen
=======================

* **Nur PSR-7-Middleware:** kann zuvor verlorene Rohinformationen nicht prüfen.
* **Interne GuzzleClientFactory ersetzen:** bindet die Erweiterung unnötig
  an eine interne Core-API und ersetzt mehr als die benötigte URI-Grenze.
* **Eine Ersatzklasse für beide Core-Zweige:** ignoriert unterschiedliche
  Vererbungsregeln bei :literal:`readonly`.
* **Nur Versionsnummer oder jede fremde Registrierung akzeptieren:** belegt
  weder die tatsächliche ABI noch die aktive Factory-Identität.

.. _adr-0016-consequences:

Konsequenzen
============

Die registrierte Core-Factory erhält eine frühere Validierungsgrenze ohne
einen zweiten HTTP-Stack. Inkompatible ABI oder widersprüchliche Registrierung
führen zum kontrollierten Fehler. Projekte mit eigener Factory-Ersetzung
brauchen eine ausdrückliche Integration; frühe Bootstrap-Aufrufe,
unabhängige Clients und direkte Sockets bleiben außerhalb dieser Grenze.
Bei bereits erzeugten PSR-18-Requests bleibt die Verantwortung für die rohe
URI beim Aufrufer, siehe :ref:`api-raw-uri` und :ref:`security-coverage`.

.. _adr-0016-verification:

Quellen und Nachweis
====================

Die aktuelle Umsetzung steht unter :file:`Classes/Http/` in
:file:`RequestFactoryCompatibility.php`, :file:`RawRequestFactoryRegistration.php`,
:file:`RawRequestGuardTrait.php` und den beiden Ersatzklassen; die Verdrahtung
steht in :file:`Configuration/Services.yaml` und :file:`ext_localconf.php`.

:file:`Tests/Unit/RequestFactoryCompatibilityTest.php`,
:file:`Tests/Unit/RequestFactoryAbiContractTest.php` und
:file:`Tests/Unit/RawRequestFactoryModeTest.php` prüfen ABI, Registrierung und
Modusgrenzen. Die echten Core-/Bootstrap-Nachweise stehen getrennt unter
:ref:`verification-report` und :file:`Tests/Functional/production-bootstrap.php`.
Dieser ADR dokumentiert die heute geprüften Quellen; seine Erstellung ist
kein neuer Lauf dieser Tests und keine neue Native-Messung.

.. _adr-0016-reassessment-trigger:

Anlass für Neubewertung
=======================

Eine Änderung der öffentlichen Core-Signatur, Vererbung, Registrierung oder
PSR-Aliasverdrahtung verlangt erneute ABI- und Bootstrap-Prüfung. Eine
erweiterte Abdeckungszusage braucht Nachweise für den zusätzlichen Pfad.
