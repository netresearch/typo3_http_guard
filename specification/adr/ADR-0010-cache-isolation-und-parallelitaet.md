# ADR-0010: Adressmemoisierung erlauben, Berechtigungs- und Verbindungspooling isolieren

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-015, HG-025, HG-026, HG-027, HG-040, HG-044  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Mehrere gleichzeitige Requests können dieselbe Origin unter unterschiedlichen Freigaben oder DNSantworten erreichen. Guzzles/cURLs Pooling und optionale Share-Handles können DNS- oder Verbindungszustand wiederverwenden. Ein neuer Pin allein ist deshalb kein ausreichender Nachweis requestbezogener Isolation. [S12, S14]

## Entscheidung

Version 1 isoliert jede unabhängige Transferlease samt cURL-Multi-Handle, DNS- und Connectionzustand. Keine gemeinsamen Share-Handles, keine Übernahme fremder Poolverbindungen, keine stillen Alt-Svc- oder HSTS-Routenwechsel. Ein Request mit internem Grant darf keinen späteren Public-Request über seinen Verbindungspool privilegieren.

Ein begrenztes positives DNSmemo ist erlaubt: standardmäßig höchstens fünf Sekunden und 32 Hosts, bei kürzerem bekannten TTL entsprechend weniger. TTL 0 und negative Antworten werden nicht positiv gecacht. Schlüssel enthalten Resolveridentität/-konfiguration. Jede Verwendung klassifiziert die Adressen neu gegen die aktuelle Policy; es wird niemals ein boolesches Allow gecacht.

ConnectionPlans sind einmalige, kurzlebige Versuchsdaten. Verzögerte Arbeit autorisiert beim tatsächlichen neuen Versuch, nicht bei ihrer Einplanung. Policywechsel gelten für neue Versuche; bereits laufende erlaubte Transfers werden nicht als automatisch widerrufen ausgegeben.

## Verworfene Alternativen

**Gemeinsamer Pool plus Pin:** erfordert zusätzliche, komplexe Isolationsevidenz. **Globaler Host-Allow-Cache:** vermischt Kontexte. **Gar keine Memoisierung:** sicher möglich, verursacht aber vermeidbare Doppelauflösung. **Live-Widerruf laufender Transfers:** eigenes Laufzeitfeature, nicht durch einen Cacheflush erledigt.

## Konsequenzen

Parallele Sicherheitsentscheidungen bleiben voneinander unabhängig. v1 verzichtet auf Wiederverwendung über unabhängige Versuche und akzeptiert Handshakekosten. Bei hohem Requestvolumen muss dies gemessen werden; die Bibliothek puffert Bodies nicht zusätzlich.

## Nachweis und Abnahme

T038, T052, T053, T054, T055, T056, T057, T075, T079, T084. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Pooling kann erst nach einer neuen ADR mit Schlüssel-/Invalidierungsmodell, Concurrencytests und Wire-Beweis eingeführt werden. Ein Performanceziel allein ist kein Nachweis.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
