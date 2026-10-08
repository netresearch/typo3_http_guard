# ADR-0007: Nur kontrolliertes cURL; Proxies und PHP-Streams nicht in v1

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-005, HG-020, HG-021, HG-022, HG-028, HG-042  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Ein cURL-Pin wird von PHP-Streams nicht umgesetzt. Bei Proxybetrieb kann die Originauflösung beim Proxy liegen; die lokal geprüfte IP ist dann kein Beweis für dessen Zielverbindung. Guzzle-Majors unterscheiden sich in erlaubten Raw-Optionen. [S05, S08, S12, S13, S17]

## Entscheidung

Enforce verlangt einen getesteten cURL-/curl-multi-Adapter. `stream=true`, fremde Handler, rohe cURL-Optionen und nicht zertifizierte Transportsharing-Einstellungen werden abgelehnt. cURL-Konstanten und Verhalten werden auf tatsächliche Verfügbarkeit geprüft. Die funktionale Multi-Address-Mindestversion allein ist keine akzeptierte Securitybaseline.

Version 1 unterstützt keine Proxies. Explizite und tatsächlich geerbte Proxykonfiguration wird erkannt; weder Versand darüber noch stilles Umgehen ist erlaubt. Nach bestandener Prüfung unterbindet der kontrollierte Adapter unbeabsichtigtes Proxyerben. Ein eingehender HTTP-Header `Proxy` ist keine vertrauenswürdige Prozesskonfiguration.

Die gemeinsame Bibliothek enthält getrennte, getestete Guzzle-7/8-Optionenadapter. Transportumlenkende Optionen bleiben Guard-eigen. CA-Bundles, mTLS und freigegebene Timeouts werden nicht durch rohe Overrideoptionen ersetzt.

## Verworfene Alternativen

**Alle Guzzlehandler zulassen:** keine einheitliche Garantie. **Proxy als privaten Endpoint allowlisten:** prüft nur den Proxy, nicht dessen Originzugriff. **Automatischer Direct-Fallback:** umgeht Unternehmensrouting. **Jedes beliebige Raw-cURL-Flag durchreichen:** öffnet unabhängige Ziel-/Resolverwege.

## Konsequenzen

Der v1-Umfang ist klar und prüfbar, schließt aber Proxyinstallationen aus. Ein Supportfehler ist sichtbar statt still unsicher. Die Entwicklung muss echte Versionskombinationen testen, nicht nur Composerauflösung.

## Nachweis und Abnahme

T040, T041, T042, T043, T044, T058, T080, T084. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Ein Proxyadapter braucht eine eigene ADR, ein definiertes Trust-Modell und einen Nachweis am tatsächlichen Originpfad. PHP-Streams benötigen ebenfalls eine eigenständig nachgewiesene Bindung vor Freigabe.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
