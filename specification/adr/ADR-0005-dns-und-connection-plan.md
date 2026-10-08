# ADR-0005: Geprüfte Adressmenge unmittelbar an die Verbindung binden

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-013, HG-014, HG-015, HG-016, HG-027, HG-044  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Vorprüfung und unabhängige Transportauflösung können verschiedene IPs liefern. Vault hat dies durch `CURLOPT_RESOLVE` adressiert und anschließend Leerantworten geschlossen behandelt. Es behält jedoch eine Literal-Allowlist-Ausnahme bei. DNS und NSS-/Hosts-Auflösung sind nicht gleichwertig. [S05-S07, S09, S19]

## Entscheidung

Jeder Versuch erzeugt nach Normalisierung und Policyprüfung einen unveränderlichen ConnectionPlan. Alle verwertbaren A-/AAAA-Adressen werden geprüft; ein verbotener Kandidat verwirft den gesamten Versuch. Keine brauchbare Antwort bedeutet Ablehnung, auch bei einem freigegebenen Host. Statische Hostzuordnungen liefern ebenfalls zu prüfende Adressen, keine Blankofreigabe.

Der Transport bekommt genau diese Menge in einem Multi-Address-Pin pro Host-Port-Paar. Originalhostname, TLS-SNI und Zertifikatsprüfung bleiben erhalten. Ein unerreichbarer Pin darf keinen ungeprüften DNSfallback auslösen. Ungültige Records, Pinformate oder fehlschlagende Optionsetzer dürfen nicht still ignoriert werden.

Auflösungsumfang und Memoisierung sind begrenzt. Ein synchrones `dns_get_record()` wird nicht als hart unterbrechbarer Resolver ausgegeben; Betriebssystemgrenzen und gemessenes Verhalten gehören zur Abnahme.

## Verworfene Alternativen

**Nur frisches DNS vor jedem Send:** bleibt ein Check-to-connect-Fenster. **Nur gefährliche IPs herausfiltern:** verdeckt gemischte Vertrauenszonen und wird für v1 abgelehnt. **Expliziter Host darf bei DNSfehler unkontrolliert weiter:** verletzt die verbindungsgebundene Garantie. **URL durch IP ersetzen:** erschwert korrekte Host-/TLS-Semantik.

## Konsequenzen

Die Auswahl ist nachvollziehbar und testbar. Split-DNS-Konfigurationen mit gemischten erlaubten/verbotenen Antworten werden bewusst verweigert. Nicht in DNS bekannte Namen brauchen eine statische Zuordnung oder einen später zertifizierten Resolveradapter.

## Nachweis und Abnahme

T022, T023, T024, T025, T026, T027, T028, T029, T030, T031, T032, T033, T078. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Weitere Resolver dürfen aufgenommen werden, wenn sie Kandidaten vollständig und begrenzt liefern und niemals die Transportauflösung unkontrolliert freigeben.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
