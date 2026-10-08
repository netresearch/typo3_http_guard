# ADR-0001: Schutz des ausgehenden HTTP-Pfads, keine PHP-Firewall

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-001, HG-002, HG-018, HG-039, HG-042  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Eine unzuverlässige URL kann den Server zum Zugriff auf ein internes Ziel veranlassen. Der normale TYPO3-HTTP-Stack ist ein geeigneter gemeinsamer Eingriffspunkt. Andere PHP-Clients und direkte Sockets existieren daneben; auch ein pro Request ersetzter Handler kann die Middleware umgehen. Der Core stellt keine Prozesssandbox bereit. [S01-S03, S18]

## Entscheidung

Das Produkt schützt ausschließlich dokumentierte, geprüfte Sendewege: den integrierten Standardstack und explizit integrierte Clients. Vor einem nicht erlaubten Zielkontakt muss der Guard ablehnen. DNS-Anfragen an den konfigurierten Resolver sind davon getrennt; der Guard verspricht nicht, überhaupt keine Netzwerkpakete zu senden.

Angreifer dürfen URLs, Antworten und DNS ihrer eigenen Zonen beeinflussen. Installierter PHP-Code, Policydateien, TLS-/Resolverkonfiguration und Betriebssystem gehören zur vertrauenswürdigen Basis. Endpoint-Grants verhindern versehentliche Berechtigungserweiterungen, sind aber kein Schutz gegen bösartigen Code im selben Prozess. Der Guard ist auch keine Autorisierung für einzelne Pfade oder Datensätze auf einem erlaubten Server.

Dokumentation und Diagnose verwenden "global" nur im Sinn des erfassten TYPO3-Stacks. Ausgehende Firewallregeln und Dienstauthentifizierung bleiben ergänzende Kontrollen.

## Verworfene Alternativen

**Prozessweite Netzwerksperre in PHP:** ohne Kontrolle aller Netzwerkfunktionen nicht erfüllbar. **Nur URL-Syntaxvalidierung:** kontrolliert weder DNS noch tatsächliche Verbindung. **Nur Netzwerkfirewall:** wertvoll, liefert aber allein keine fachlich getrennten Clientfreigaben.

## Konsequenzen

Das Schutzversprechen ist messbar und nicht irreführend. Nicht integrierte SDKs bleiben eine bewusste Abdeckungslücke; ihre Migration erfordert eigene Adapter. Der Betrieb muss beide Ebenen inventarisieren.

## Nachweis und Abnahme

T001, T003, T035, T074, T077. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine neue Integration oder ein erweitertes Versprechen benötigt einen eigenen Transportnachweis. Eine künftige Prozess-/Netzwerksandbox ist ein separates Produktziel.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
