.. _security:

=======================
Schutzmodell und Grenzen
=======================

.. _security-attempt:

Ein Verbindungsversuch
=====================

Die äußere Grenze prüft Authority, Kontext, erlaubte Optionen und die
Registryposition. Danach erreicht jeder neue SDK-Leaf-Versuch das letzte
Guard-Terminal. Dort wird die Policy frisch bewertet. Ein ungültiger
Kandidat verwirft die gesamte Adressemenge. Erst beim Fortschritt des
Versands entsteht der native Transfer; nach DNS-/Body-Vorbereitung werden
Profilgültigkeit und Plan nochmals geprüft.

Die genehmigten Adressen werden mit :literal:`CURLOPT_RESOLVE` vollständig
an den Host gebunden. URL-Authority und Hostname bleiben für Host-Header,
TLS-SNI und Zertifikatsprüfung erhalten. Es gibt keinen ungebundenen
DNS-/Stream-/Default-Handler-Ersatz, wenn Pinning oder Verbindungsaufbau
fehlschlagen.

Jeder native Versuch besitzt einen eigenen cURL-multi-Handler und eine
cURL-Factory ohne retained Easy-Handle-Pool. Fresh-Connect, Forbid-Reuse,
ausgeschalteter nativer DNS-Cache, feste Protokolle und fehlende externe
Sharing-Handles verhindern die Übernahme alter Route-/Verbindungszustände.
Ein versteckter SDK-Rewind-Retry kann den Terminalpfad nicht umgehen.

Abbruch, Callbackfehler und Transportfehler räumen die Ressourcen des
zugehörigen Versuchs auf. Ein Policyfehler widerruft den betreffenden
Aufruf, selbst wenn eine fremde Retry-Entscheidung unkritisch erneut senden
wollte. Reguläre Netzwerk-Retries benötigen einen neuen Policyplan.

.. _security-dns:

DNS und statische Auflösung
==========================

Der Defaultresolver fragt A, AAAA und CNAME für den exakten absoluten
FQDN ab. Er nutzt weder NSS noch Suchdomain-Erweiterungen oder
:file:`/etc/hosts` als stillen Ersatz. Statische Hosts sind ausschließlich
die expliziten Einträge der Policy.

Der DNS-Wire-Backend verwendet numerische, vertrauenswürdige Nameserver aus
:file:`/etc/resolv.conf`. Diese Datei wird erst bei der ersten DNS-Anfrage
gelesen. Bis zu drei Nameserver sind erlaubt. Bekannte Zeilen wie
:literal:`search`, :literal:`domain`, :literal:`options` und
:literal:`sortlist` werden erkannt, ihre Such-/NSS-Wirkung aber nicht
übernommen; unbekannte Serverkonfiguration wird abgelehnt.

UDP-Antworten werden auf Transaktions-ID, Frage, RCODE, Typen, Längen und
Kompressionsgrenzen geprüft. Bei gesetztem TC-Bit wird derselbe Query über
TCP mit Längenpräfix innerhalb derselben Queryfrist wiederholt. Eine erneut
abgeschnittene, widersprüchliche, leere oder unvollständige Antwort wird
nicht akzeptiert. Die CNAME-Kette wird vollständig bis zur terminalen
Adresse verfolgt und begrenzt.

Die Defaultfrist beträgt eine Sekunde je Nameserver und Query. Bei drei
Nameservern, neun Namen in der maximalen CNAME-Kette und drei Querytypen
kann die sequenzielle Auflösung theoretisch bis zu 81 Sekunden benötigen,
zuzüglich lokalem Verwaltungsaufwand. Dies ist keine zugesicherte
Gesamtfrist für Anwendung und HTTP. SDK-Timeouts beginnen nicht als
allgemeiner Resolver-Abbruchvertrag. Ein eigener Resolver ist eine
vertrauenswürdige interne Erweiterung und muss dieselbe vollständige,
begrenzt überprüfbare Antwortsemantik erfüllen.

Positive DNS-Memo-Einträge gelten höchstens fünf Sekunden und nie länger
als die kleinste verbleibende TTL der benutzten Kette. Auch ein Memo-Treffer
passiert die Adress-, Betreiber-, Profil- und Ablaufprüfung des verwendeten
Policy-Snapshots.

.. _security-addresses:

Adressregeln
============

Adressen werden binär ausgewertet, einschließlich CIDR-Grenzen und
IPv4-mapped IPv6. Nicht öffentliche oder besondere Netze sind in der
Standardpolicy gesperrt. Die Regeln basieren auf versionierten IANA-Registern
und zusätzlichen fest dokumentierten Metadaten-Sperren. Link-Local,
Multicast, Dokumentations-/reservierte Bereiche und Metadatenziele erhalten
keine allgemeine Endpoint-Ausnahme.

Enge private RFC1918-, ULA- und CGNAT-Netze sowie einzeln freigegebenes
Loopback können für gebundene Clients genehmigt werden. Betreiber-Sperren
bleiben vorrangig. Die genaue Ausnahmekonfiguration steht unter
:ref:`configuration-endpoints`.

Die ausgelieferten Regeln und die Quellen-/Hashmetadaten liegen unter
:file:`Resources/Private/HttpGuard/data/security-corpus/`. Eine Änderung
dieser Regeln ist ein Policyupdate mit neuer Revision und erneuter
Regression-/Wire-Prüfung; sie gehört nicht in eine spontane Laufzeitfreigabe.

.. _security-redirects:

Redirects, Credentials und TLS
=============================

Response-Middleware darf eine Location verändern; die Guard-Grenze prüft
die tatsächliche Location danach und vor dem SDK-Folgeversand. Gewöhnliche
Requests bleiben auf derselben Origin und dürfen kein HTTPS auf HTTP
herabstufen. Dies gilt auch für 307/308 mit erhaltener Methode und Body.
Ein Profil kann Redirects ganz verbieten. Public Fetch verwendet eigene
Requests ohne Integrationsgeheimnisse und kann öffentliche Originwechsel
unter derselben Adresspolicy gestatten. PSR-18 bleibt ohne Redirect-Folgen.

TLS-Verifikation, CA-Bundles und mTLS bleiben Transportoptionen unter der
zusätzlichen TLS-Policy. HTTP Guard ist kein Ersatz für korrekt konfigurierte
Zertifikate, CA-Trust oder Secretverwaltung.

.. _security-proxy:

Proxy und eingehende Header
==========================

Explizite Proxyrouten und echte HTTP-/HTTPS-/ALL-/NO_PROXY-Prozessvariablen
werden abgelehnt, auch bei einem vermuteten NO_PROXY-Treffer. Dabei liest
der Guard Prozessvariablen lokal und übernimmt nicht den eingehenden
:literal:`Proxy`-HTTP-Header als vertrauenswürdige Proxykonfiguration.
PHP-SAPIs können diesen Header in :php:`$_SERVER` entfernen; eine solche
SAPI-Bereinigung und die unabhängige Prozessprüfung sind unterschiedliche
Mechanismen.

.. _security-coverage:

Abdeckung und Vertrauensgrenze
=============================

Geschützt sind der registrierte Core-RequestFactory-Pfad nach Aktivierung
und die gebundenen Clients der Guard-Factories. Bei widersprüchlichen
Middlewarepositionen oder ABI-/Versionskonflikten wird der geschützte
Pfad ausdrücklich abgelehnt. Bestehende Core-Kontextrestriktionen bleiben
kumulativ. Eine flache Legacy-Hostliste für Vault erteilt dem öffentlichen
Core-Client keine privaten Rechte.

Die Extension kann keine beliebige PHP-Codeausführung einschließen.
Folgende Pfade benötigen eine eigene Integration:

* Frühe Bootstrap-Aufrufe vor der Registrierung.
* Unabhängige Guzzle-Clients, fremde SDKs, direkte cURL- oder Socketaufrufe.
* Ein requesteigener Guzzle-Handler, der den Stack bereits vor dem ersten
  Middlewareeintritt ersetzt.
* Eingehende PSR-15-Middleware und sonstige nicht ausgehende HTTP-Verarbeitung.
* Vault ohne den bewussten optionalen Adapterpatch.

Rohe URI-Informationen können bereits vor PSR-18 verloren gehen; die genaue
Kompositionspflicht steht unter :ref:`api-raw-uri`. Policy-Snapshots werden
nicht automatisch in alten Workern aktualisiert; siehe
:ref:`operations-changes`. Beobachtungsmodus, Offline-Prüfung und
Agentenreview sind keine alternative Durchsetzung am echten Transport.
