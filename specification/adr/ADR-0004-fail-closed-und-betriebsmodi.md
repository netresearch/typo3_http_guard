# ADR-0004: Enforce als Standard, Observe nur als sichtbarer Migrationsmodus

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-003, HG-004, HG-021, HG-033, HG-034, HG-043  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Eine neu installierte Securityextension erzeugt eine Schutzannahme. Ein stiller Fallback ohne cURL oder bei ungültiger Policy würde diese Annahme verletzen. Gleichzeitig braucht ein Bestandsprojekt eine kontrollierte Inventarisierung seiner internen Verbindungen. Vault bietet heute absichtlich ein schwächeres Verhalten ohne cURL. [S04-S06, S16]

## Entscheidung

Neue Installation: `enforce`, keine internen Grants. Fehlende optionale Konfiguration verwendet sichere Defaults; ungültige vorhandene Konfiguration scheitert. Nicht unterstützte Transportbedingungen blockieren vor Kontakt zum Ziel oder Proxy.

`observe` ist eine explizite Betreiberentscheidung. Er protokolliert soweit prüfbar `would_deny` bzw. `unverifiable`, delegiert aber an den bisherigen Transport und darf nicht als geschützt gelten. Ein Diagnoseproblem soll dort nicht als erfolgreiche Sicherheitsprüfung erscheinen. Vorhandene Core-/Vaultkontrollen bleiben unangetastet.

`disabled` ist transparent und sichtbar. Es gibt keinen automatischen Wechsel von Enforce zu Observe oder Disabled. Rollback und Ausnahmen benötigen Änderung an vertrauenswürdiger Projektkonfiguration. Kein Schalter aus einem HTTP-Parameter oder entfernten Responseheader.

## Verworfene Alternativen

**Observe als Installationsdefault:** liefert zunächst keinen erwarteten Schutz. **Warnung statt Block bei Transportlücken:** schwer erkennbarer Verlust der Invariante. **Fail-open bei Konfigurationsfehlern:** ein Tippfehler würde Berechtigungen erweitern.

## Konsequenzen

Fehler sind eindeutig; Bestandsinstallationen können aber nach Aktivierung ausfallen, bis legitime interne Zugriffe erfasst sind. Der Rollout braucht Inventar, Diagnose und bewusste Freigabe. Observe-Telemetrie ist keine Abnahme des Enforce-Transports.

## Nachweis und Abnahme

T005, T006, T042, T065, T066, T067. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine Änderung des Installationsdefaults ist eine produktweite Security-/Kompatibilitätsentscheidung. Automatische Herabstufung bleibt unabhängig davon unzulässig.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
