.. _operations-reasons:

========================
Ablehnungsgründe verstehen
========================

Reason-Codes sind feste technische Werte. Die folgende Zuordnung beschreibt
die Untersuchung; sie erlaubt keinen automatischen Ersatztransport.

.. list-table:: Codes von PolicyException
    :header-rows: 1
    :widths: 25 38 37

    * - Code
      - Auslöser
      - Nächster Prüfschritt
    * - :literal:`invalid_target`
      - Ungültige URI, verbotene rohe Syntax, Userinfo oder Fragment.
      - Ursprünglichen String vor PSR-7-Erstellung untersuchen.
    * - :literal:`scheme_forbidden`
      - Anderes Scheme als HTTP/HTTPS.
      - Tatsächlichen Anwendungspfad korrigieren.
    * - :literal:`authority_mismatch`
      - Host-Header passt nicht zur kanonischen URI-Authority.
      - Host-/Port-Overrides im aufrufenden Code entfernen.
    * - :literal:`address_forbidden`
      - Verbotene Adressklasse, Betreiber-Sperre oder Kandidat außerhalb
        eines gebundenen Netzes.
      - Vollständige IP-Liste und exaktes Profil prüfen.
    * - :literal:`resolution_unverified`
      - DNS unvollständig, ungültig, leer, nicht erreichbar oder nicht
        überprüfbar; bei Offline-Diagnose kein statischer Eintrag.
      - DNS-Serverkonfiguration und statische Hostdaten prüfen.
    * - :literal:`resolution_limit`
      - Adress-/CNAME-Grenze oder Zyklus.
      - DNS-Kette beheben; keine Kandidaten abschneiden.
    * - :literal:`endpoint_mismatch`
      - Origin oder Methode entspricht nicht dem gebundenen Profil.
      - Servicebindung, Zielport und Methode abgleichen.
    * - :literal:`grant_invalid`
      - Unbekannte, fremde oder abgelaufene Bindung.
      - Profilgültigkeit prüfen und Client korrekt neu erzeugen.
    * - :literal:`transport_unsupported`
      - Fehlendes cURL/cURL-multi, nicht unterstützte Hauptversion oder
        Untergrenze, inkompatible Core-/SDK-API oder zweiter nativer
        Versuch derselben Lease.
      - :literal:`doctor`, Versionsbereiche, APIs und PHP-Funktionen prüfen.
    * - :literal:`proxy_unsupported`
      - Explizite Proxyoption oder echte Proxy-Prozessvariable.
      - Deployment-Umgebung und Proxyanforderung mit Betreiber klären.
    * - :literal:`option_forbidden`
      - Unbekannte oder unkontrollierte SDK-/Transportoption.
      - RequestFactory-Defaults und Requestoptionen prüfen.
    * - :literal:`redirect_forbidden`
      - Verbotene Redirect-Origin, Downgrade oder ungültige Redirectoption.
      - Tatsächliche Location und Profilregeln untersuchen.
    * - :literal:`configuration_invalid`
      - Schemafehler, widersprüchliche Registry oder Middlewareposition.
      - :literal:`config-check`, :literal:`doctor` und Serviceverdrahtung
        prüfen; fehlerhaften Deploymentstand beheben.

Interne DNS-Fehler werden an der Resolvergrenze gegebenenfalls als
:literal:`resolution_unverified` zusammengefasst. Ein Reason-Code ist kein
vollständiger Netzwerkdiagnosebericht und enthält absichtlich keine Secrets.
