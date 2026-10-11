.. _adr-0017:

=========================================================
ADR-0017: Kontrollierte DNS-Abfragen für den Defaultresolver
=========================================================

:Status: Angenommen
:Datum: 2026-10-10
:Ablösung: ADR-0005 nur hinsichtlich der Defaultresolverwahl.

.. _adr-0017-context:

Kontext
=======

:ref:`adr-0005` verlangt eine vollständige geprüfte Adressmenge, die an die
Verbindung gebunden wird. Der historische Vorschlag nennt zusätzlich
:literal:`dns_get_record()` und dessen fehlende harte Unterbrechbarkeit.
Für die aktuelle Umsetzung sind Auflösungsweg, Antwortprüfung und
Zeitgrenzen ausdrücklich kontrolliert. Ein impliziter NSS-, Hosts- oder
Suchdomain-Fallback würde ein anderes Ziel oder eine andere Vertrauensbasis
einführen.

.. _adr-0017-decision:

Entscheidung
============

:literal:`LibraryServiceFactory` verdrahtet :literal:`StaticThenDnsResolver`
mit :literal:`WireDnsQuery`. Nur explizite statische Policy-Einträge umgehen
die DNS-Abfrage; ihre Adressen unterliegen weiterhin der Policy. Alle übrigen
Namen werden als absolute FQDN mit A-, AAAA- und CNAME-Abfragen aufgelöst.
Es gibt keinen impliziten NSS-, :file:`/etc/hosts`- oder Suchdomain-Fallback.

Der Wire-Backend verwendet numerische, vertrauenswürdige Nameserver aus
:file:`/etc/resolv.conf`, das erst bei der ersten DNS-Abfrage gelesen wird.
Höchstens drei Nameserver sind zulässig. Bekannte Direktiven werden erkannt,
ihre Such- und NSS-Semantik wird aber nicht übernommen. Unbekannte oder
ungültige Serverkonfiguration wird abgelehnt.

:literal:`DnsPacketCodec` prüft unter anderem Transaktions-ID, Frage,
Antwortstatus, Typen, Längen und Kompressionsgrenzen. Nur ein gesetztes
TC-Bit veranlasst die Wiederholung derselben Frage über längenkodiertes TCP
innerhalb derselben Abfragefrist. Erneute Trunkierung oder unverifizierbare
Antworten liefern keine nutzbare Auflösung. Der Resolver verfolgt die
vollständige CNAME-Kette und begrenzt Hops und Adressen.
:literal:`NativeOperation` wandelt native Warnungen und Notices in feste
Fehler um, ohne deren ursprünglichen Text offenzulegen.

Die Standardfrist beträgt eine Sekunde je Nameserver und Abfrage.
Positive Memo-Einträge sind auf höchstens fünf Sekunden und den kleinsten
verbleibenden TTL begrenzt; ihre Nutzung durchläuft erneut die Policyprüfung.
Die vollständige Adressprüfung und das Verbindungspinning aus ADR-0005
bleiben unverändert verbindlich. Dieser ADR ersetzt ausschließlich dessen
historische Wahl von :literal:`dns_get_record()` als Defaultpfad.

.. _adr-0017-alternatives:

Verworfene Alternativen
=======================

* **dns_get_record als unbeschränktes Standardbackend:** liefert keine
  vergleichbare ausdrückliche Kontrolle der hier verwendeten Abfragefristen.
* **NSS, Hosts oder Suchdomains still hinzunehmen:** erweitert den geprüften
  Auflösungsweg und kann abweichende Ziele liefern.
* **Nur gefährliche Adressen ausfiltern oder bei DNSfehler weitergehen:**
  verletzt die vollständige Adressprüfung aus ADR-0005.
* **TCP nach jedem UDP-Fehler:** erweitert den Fallback über den ausdrücklich
  geprüften Trunkierungsfall hinaus.

.. _adr-0017-consequences:

Konsequenzen
============

DNS hat einen nachvollziehbaren und begrenzten Ablauf, bleibt aber eigener
Netzwerkverkehr zu vertrauenswürdigen Resolvern. Der Guard verspricht keine
allgemeine Netzwerksperre für den PHP-Prozess. NSS-spezifische Namen brauchen
eine ausdrückliche statische Zuordnung oder einen qualifizierten Resolver.
Ein vertrauenswürdiger Nameserver darf zur privaten Infrastruktur gehören;
das erlaubt keinen HTTP-Zugriff auf private Ziele. DNS-Vertrauensbasis und
Policy für das anschließende HTTP-Ziel haben unterschiedliche Aufgaben.

Drei Nameserver, neun Namen in der maximalen CNAME-Kette und drei Abfragetypen
können sequenziell theoretisch bis zu 81 Sekunden plus lokalen Overhead
beanspruchen. Das ist keine garantierte Gesamtfrist für Anwendung und HTTP.
Ein SDK-Timeout stellt keinen allgemeinen Resolver-Abbruchvertrag her.
Eigene Resolver bleiben vertrauenswürdige interne Erweiterungen und müssen
vollständige, begrenzte und verifizierbare Antworten liefern.

.. _adr-0017-verification:

Quellen und Nachweis
====================

Die Umsetzung steht in :file:`Classes/HttpGuard/WireDnsQuery.php`,
:file:`Classes/HttpGuard/DnsPacketCodec.php`,
:file:`Classes/HttpGuard/StaticThenDnsResolver.php` und
:file:`Classes/HttpGuard/NativeOperation.php` sowie
:file:`Classes/Service/LibraryServiceFactory.php`. Der aktuelle Umfang und
die Fristen stehen unter :ref:`security-dns`.

:file:`Tests/HttpGuard/Unit/Policy/WireDnsQueryTest.php` prüft TCP bei
Trunkierung, numerische Nameserver, Blackhole-Fristen und verzögertes Lesen
der Serverkonfiguration. Codec-/Resolvergrenzen und echte Transportkontakte
werden zusätzlich unter :file:`Tests/HttpGuard/Unit/Policy/` und
:file:`Tests/HttpGuard/Integration/DnsPolicyTransportTest.php` geprüft.
Die bereits ausgeführten, quellengebundenen Ergebnisse stehen unter
:ref:`verification-report`; dieser ADR erzeugt keinen neuen Laufzeitnachweis.

.. _adr-0017-reassessment-trigger:

Anlass für Neubewertung
=======================

Neue Resolverprotokolle, NSS-Integration, Suchdomainverhalten oder Änderungen
an Fristen und Caching erfordern einen neuen Vertrauens- und Nachweisumfang.
Die Aufnahme eines anderen Backends hebt die vollständige Prüfung und
Verbindungsbindung der Adressmenge nicht auf.
