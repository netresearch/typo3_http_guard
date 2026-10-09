.. _operations:

=======
Betrieb
=======

.. _operations-diagnostics:

Diagnosebefehle
==============

Alle Befehle schreiben JSON. :literal:`config-check`, :literal:`doctor`
und :literal:`legacy-report` senden weder Ziel-HTTP noch DNS-Anfragen.
:literal:`policy-check` darf DNS verwenden, sendet jedoch kein Ziel-HTTP.
Mit :literal:`--no-dns` bleibt auch dieser Befehl offline.

.. code-block:: bash
    :caption: Composer-Projekt; für klassische Projekte CLI-Pfad ersetzen

    vendor/bin/typo3 http-guard:config-check
    vendor/bin/typo3 http-guard:doctor
    vendor/bin/typo3 http-guard:legacy-report
    vendor/bin/typo3 http-guard:policy-check https://www.example.org --no-dns
    vendor/bin/typo3 http-guard:policy-check https://erp.internal.example:8443 --endpoint erp-orders

.. list-table:: Diagnose und Bedeutung
    :header-rows: 1

    * - Befehl
      - Ergebnis
    * - :literal:`config-check`
      - Schema, Modus, Policyrevision, Profilanzahl und Review-/TLS-Warnungen.
    * - :literal:`doctor`
      - Tatsächliche Versionen, Registry, cURL, Proxyvariablennamen und
        deklarierte Abdeckung. :literal:`protected` gilt nur im unterstützten
        Enforce-Modus ohne Proxykonflikt.
    * - :literal:`legacy-report`
      - Bestandsaufnahme bisheriger HTTP-Kontexte, Hostlisten und Optionen.
        Erzeugt weder Profile noch automatische Freigaben.
    * - :literal:`policy-check <url>`
      - Aktuelle diagnostische Bewertung als GET, optional mit benanntem
        Profil. Keine übertragbare Grant-/Transportberechtigung.

.. list-table:: Verwendete Exitcodes
    :header-rows: 1

    * - Code
      - Bedeutung
    * - 0
      - Erfolgreiche Prüfung beziehungsweise erlaubte Policyentscheidung.
        :literal:`config-check` allein sagt nichts über geschützten Versand.
    * - 2
      - Policyablehnung, nicht verifizierbare Beobachtung oder bei
        :literal:`doctor` ausdrücklich ungeschützter Modus.
    * - 3
      - Ungültige Konfiguration/Registry oder nicht unterstützter
        Transport/Proxy. Die Policy wird nicht stillschweigend abgeschaltet.
    * - 4
      - Auflösung nicht überprüfbar, beispielsweise ein DNS-Host ohne
        statischen Eintrag bei :literal:`policy-check --no-dns`.

Die genaue JSON-Nutzlast bleibt maßgeblich: :literal:`httpSent=false`
bedeutet, dass der Diagnosebefehl kein Ziel-HTTP gesendet hat, nicht dass
eine Anwendung später automatisch geschützt ist.

.. _operations-rollout:

Einführung im Betreiberprojekt
==============================

1. Ausgehende HTTP-Pfade inventarisieren: Core RequestFactory, gebundene
   PSR-18-Clients, Vault, fremde SDKs, direkte Guzzle-/cURL-Aufrufe und frühe
   Bootstrap-Verbindungen. Jeden Pfad seiner tatsächlichen Integration
   zuordnen.
2. In Staging die Schema-, Registry-, Legacy- und Transportdiagnose
   ausführen. Core-Kontexte und vorhandene Restriktionen prüfen.
3. Bei Bedarf ausdrücklich :literal:`observe` einsetzen. Beobachtungen
   dienen der Untersuchung; beobachtete Anfragen werden nicht automatisch
   zu erlaubten Profilen. Der Modus bietet keine erzwungene Pin-Bindung.
4. Interne Integrationen einzeln begründen. Exakte Origins, Methoden,
   Verantwortliche, enge Netze und geeignete Ablaufzeiten setzen. Clients
   im vertrauenswürdigen Servicecode fest an Profile binden.
5. In einem kontrollierten Pilot :literal:`enforce` aktivieren. Zulässige
   und unzulässige Ziele, Redirects, Abbrüche, OAuth-Legs und Logredaktion
   mit tatsächlichen Zielzählern prüfen.
6. Policy, Paketstand, Dependency-Lock und Nachweise zusammen freigeben.

Die Lieferung enthält technische Nachweise und reproduzierbare Fixtures.
Eine unabhängige menschliche Sicherheitsprüfung und ein Pilot mit den
tatsächlichen Betreiber-Endpunkten sind dadurch nicht ersetzt.

.. _operations-changes:

Policy ändern oder widerrufen
============================

Konfiguration, Registry, Engine und Clients besitzen einen unveränderlichen
Stand. Nach Änderungen sind TYPO3-System-/DI-Caches neu aufzubauen und
langlebige Worker kontrolliert neu zu starten. Alte Clients übernehmen keine
neue Policy allein durch eine Dateiänderung. Für einen sofort erforderlichen
operativen Stopp zusätzlich den betroffenen Worker oder Netzwerkpfad
beenden; ein bereits laufender Transfer wird nicht nachträglich als
widerrufen dargestellt.

:literal:`expiresAt` wird bei jedem neuen Versuch unmittelbar vor dem
nativen Verbindungsstart erneut geprüft. :literal:`reviewAfter` löst
ausschließlich eine Warnung aus. Einen DNS-Memo-Cache zu leeren ersetzt
keinen Registry-/Client-Austausch.

.. _operations-logging:

Entscheidungsprotokolle
======================

Die TYPO3-Integration schreibt über den Loggerkanal
:literal:`Netresearch.HttpGuard`. Der Reporter verwendet Ereignisversion 1
mit Zeitpunkt, Modus, Entscheidung, festem Reason-Code, Profil-ID,
Policyrevision, Adressklasse, Scheme, Port, Resolverquelle und einer
intern erzeugten Korrelations-ID. Ein Host kann gemäß
:ref:`configuration-logging` fehlen, als HMAC erscheinen oder ausdrücklich
im Klartext stehen.

Bodies, Headers, Cookies, Zugangsdaten, Querys, vollständige URLs,
Zertifikat-/Key-Pfade und Requestobjekte gehören nicht zum Ereignisformat.
Die Korrelations-ID stammt nicht aus einem beliebigen eingehenden Header.
Für erlaubte Entscheidungen gilt standardmäßig keine Logstichprobe.
Ablehnungen werden pro Reporter und Zeitfenster gedrosselt; Zähler erfassen
weiterhin jede Entscheidung mit begrenzten Labels. Reporterzähler leben
in der Instanz und benötigen für eine zentrale Langzeitauswertung eine
eigene Metrik-Anbindung. Loggerfehler verändern die Autorisierung nicht.

Bei :literal:`observe` zeigt :literal:`would_deny` eine festgestellte
Policyverletzung. :literal:`unverifiable` kennzeichnet fehlende überprüfbare
Transportbindung oder Auflösung. Diese Ereignisse stellen keinen Nachweis
einer erzwungenen sicheren Verbindung dar.

.. _operations-rollback:

Rollback
========

Für einen bewussten Rollback werden vorherige Policy, Extension und
Dependency-Lock gemeinsam wiederhergestellt; danach Caches neu aufbauen,
Worker neu starten und Funktions-/Auditprüfungen wiederholen.
Alternativ kann ausdrücklich :literal:`observe` oder :literal:`disabled`
deployt werden. Beide reduzieren den zusätzlichen Schutz und müssen im
Betrieb als ungeschützt ausgewiesen werden. :literal:`doctor` meldet den
bewusst ungeschützten Modus mit Exitcode 2, sofern keine andere ungültige
Fähigkeit Exitcode 3 auslöst.

Eine hohe Ablehnungsrate löst keinen automatischen Rollback aus. Ursache,
betroffener Pfad, Reason-Code und Profilbindung werden zuerst untersucht.
Bestehende Vault-Kontrollen werden durch einen solchen Moduswechsel nicht
entfernt. Die eigenen Deployment-, Cache- und Workerabläufe des
Betreiberprojekts sind im Pilot zu überprüfen.

.. toctree::
    :maxdepth: 1

    Reasons
