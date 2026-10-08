# ADR-0012: Stabile Ablehnungsgründe ohne neue Secret-Leaks

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-031, HG-032, HG-033, HG-034, HG-035  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Ein Sicherheitsguard braucht Diagnose, kann aber durch Logs selbst Credentials offenlegen. URLs können Querytokens tragen; Requests enthalten Bodies, Cookies und Authheader. Ein nach Versand ausgeführter Statistikcallback ist außerdem keine präventive Verbindungskontrolle. [S04, S08, S15]

## Entscheidung

Die Bibliothek liefert feste Reason-Codes mit typisierter Exceptionoberfläche. Synchrone und Promise-basierte Aufrufe unterscheiden sich nicht in der Policyentscheidung. Policyfehler werden nicht als normale Netzwerktimeouts getarnt. PSR-18 erkennt sie als Clientfehler; der enthaltene Request bleibt gegebenenfalls ein sensitives Objekt.

Telemetrie enthält Modus, Entscheidung, Profil-/Policykennung, Adressklasse, Scheme/Port, Resolverquelle und Korrelation. Keine Bodies, Headerwerte, URLpfade, Queries oder kompletten Exceptions. Hostanzeige ist separat konfiguriert; pseudonyme Hostwerte verwenden einen Betreiber-HMAC-Schlüssel, keinen öffentlich erratbaren Hash als angebliche Anonymisierung. Metriklabels bleiben niedrigkardinal.

Loggerfehler dürfen nie aus Deny ein Allow machen. Rate-Limits reduzieren Logfluten, nicht Denialzähler. Diagnosekommandos senden keine Probe-HTTP-Requests. `on_stats` darf eine Abweichung nachträglich melden, ersetzt aber niemals den Plan/Pinschutz.

## Verworfene Alternativen

**Vollständige Requests zur Fehlersuche loggen:** schafft Exfiltrationspfad. **Jede Ablehnung als Angriff etikettieren:** verwechselt Tippfehler, DNSprobleme und Missbrauch. **Logausfall generell als HTTP-Deny:** unnötige globale Verfügbarkeitskopplung; Vaultaudit kann separat strenger sein.

## Konsequenzen

Betrieb kann Ursachen erkennen, ohne Geheimnisse offenzulegen. Tiefere Fehlersuche erfordert gezielte synthetische Reproduktion. Andere Projektmiddlewares bleiben für ihre eigenen Logs verantwortlich; der Guard kann deren Telemetrie nicht rückwirkend bereinigen.

## Nachweis und Abnahme

T062, T063, T064, T065, T067, T068. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Neue Telemetriefelder werden vor Aufnahme auf Geheimnisgehalt und Kardinalität geprüft. Ein kompletter Requestdump ist kein zulässiger Debugkomfort.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
