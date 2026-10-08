# ADR-0013: Explizite Migration statt stiller Umdeutung bestehender Allowlisten

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-005, HG-036, HG-037, HG-038, HG-043, HG-045  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Vault benutzt einen separaten Stack, erlaubt exakte flache Hosteinträge als Ausnahme und führt ext-curl nur als Vorschlag. TYPO3s geprüfter Corestand verwendet verschachtelte Kontextallowlists und erlaubt Guzzle 7 und 8. Eine naive Extraktion kann damit zugleich Schutzgrenzen und Kompatibilität verändern. [S01, S04-S06, S13, S16]

## Entscheidung

Zuerst werden gemeinsamer Korpus und Bibliothek implementiert, dann Coreintegration und expliziter Vaultadapter. Keine Installation der globalen Extension gilt automatisch als Vaultmigration. OAuth-Tokenleg und Resourceleg erhalten getrennte Autorisierung und Pins.

Ein Legacyreport erfasst vorhandene Konfiguration, schreibt aber keine aktive interne Freigabe. Betreiber müssen Scheme, Port, CIDRs, Methoden, Zweck, Verantwortung und Ablauf bewusst ergänzen. Leere DNSantworten werden nicht mehr durch Namensfreigabe legitimiert; statische Mappings ersetzen notwendige Hosts-/NSS-Sonderfälle.

Versionswechsel und Releasehinweise benennen neue cURLpflicht, Proxy-/Streamgrenzen und strengere Ausnahmen. Entfernen bestehenden Legacyverhaltens erfolgt ausdrücklich versioniert. Die Bibliothek hat keinen versteckten Legacy-Fail-open-Schalter. Die unterstützten PHP-/TYPO3-/Guzzle-/PSR-Kombinationen werden durch aufgelöste Locks und tatsächliche CI belegt.

## Verworfene Alternativen

**Alte Listen automatisch konvertieren:** wichtige Berechtigungsdimensionen fehlen. **Globale Middleware als Vaultschutz deklarieren:** technisch falsch. **Securityfix still mit Architekturmigration verbinden:** erschwert Rollback und Review. **Alle Major-Kombinationen versprechen, die Composer erlaubt:** ersetzt keinen Transporttest.

## Konsequenzen

Migration ist nachvollziehbar und rollbackfähig, aber nicht konfigurationsfrei. Legacy und neuer Pfad können zeitlich getrennt verfügbar sein; Diagnose muss klar zeigen, welcher aktiv ist. Secrets-/Auditregressionen sind eigene Freigabekriterien.

## Nachweis und Abnahme

T039, T041, T042, T069, T070, T071, T072, T073, T080, T082. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Neue Core-/Guzzle-Majors oder eine Änderung des globalen Erweiterungspunkts erfordern erneute Integrationsabnahme. Ein gepflegter Legacyzweig wird nicht als gleichwertiger Schutz beworben.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
