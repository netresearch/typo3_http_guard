# ADR-0014: Sicherheit durch Zielkontakt- und Regressionsevidenz abnehmen

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-039, HG-040, HG-041, HG-044, HG-045  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Ein Test kann grün sein, obwohl er nur eine Exception nach bereits versandtem Request beobachtet. DNSrebinding, Redirects und geteilte Pools sind mit reinen Unitmocks nicht ausreichend belegt. Bestehende Vault-ADRs unterscheiden bereits Transport-nicht-erreicht-Tests und echte Wire-Nachweise. [S06, S08, S19]

## Entscheidung

Der normative Korpus enthält 84 Testfälle und ordnet alle 45 Anforderungen zu. Jeder kritische Ablehnungsfall braucht einen negativen Zielkontaktbeweis: zuerst Transportspy, für Transportinvarianten zusätzlich instrumentierte, hermetische Zielserver bzw. Netznachweis. Die Testumgebung darf keine realen Cloudmetadaten- oder fremden internen Dienste ansprechen.

Testschichten: Unit/Property, Libraryintegration, echte TYPO3-Registrierung, echte cURL-Netztests, Concurrency/Worker sowie Vaultnormal-/OAuth-/Streaming-/Cancellationpfade. Grenzfälle verwenden denselben Korpus. Isolierte Netze und injizierte Resolver machen Antworten reproduzierbar; keine Internetabhängigkeit für Freigabetests.

Gezielte Mutationen entfernen z.B. Pin oder Adressprüfung; die zugehörigen Tests müssen dann scheitern. Release-Gates verlangen erst Integrationsprobe, dann vollständigen P0-Nachweis und unabhängiges Securityreview. Ein neuer Dependencymajor oder Sicherheitsfix startet die relevanten Gates erneut.

## Verworfene Alternativen

**Nur Coverageprozent:** sagt nichts über die behauptete Invariante. **Nur Exceptions prüfen:** kann zu spät sein. **Nur manuelle Test-URL:** nicht reproduzierbar und potenziell gefährlich. **Vorhandene ADR-Messungen als neue Evidenz ausgeben:** verwechselt Quelle und ausgeführte Verifikation.

## Konsequenzen

Die Abnahme ist auf konkrete Garantien zurückführbar. Ein Testharness und mehrere Laufzeitkombinationen kosten Aufwand, verhindern aber unbemerkte Regressionen. Dieses Dokumentationspaket allein erfüllt diese Produktgates noch nicht.

## Nachweis und Abnahme

T001-T084; besonders T029-T033, T043-T048, T053-T061, T074-T080. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Neue Angriffswege, Transportoptionen oder Konsumenten erweitern den Korpus vor ihrer Freigabe. Ein nicht reproduzierbares Sicherheitsversprechen muss eingeschränkt statt werblich aufrechterhalten werden.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
