# ADR-0003: Zwei Middlewaregrenzen und ein kontrollierter terminaler Transport

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-001, HG-002, HG-009, HG-019, HG-021, HG-023  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Der Core installiert eigene Middlewarehandler nach Guzzles Defaults und der optionalen Core-Allowlist. Eine gewöhnliche Vorprüfung vor einem opaken `$next` garantiert nicht, welcher Transport danach läuft. Auch ein nachträgliches Umschreiben von Request-Origin oder Response-Location kann eine frühere Prüfung entwerten. [S01, S05, S12, S20]

## Entscheidung

Der TYPO3-Adapter registriert `nr/http-guard-boundary` als ersten und `nr/http-guard-terminal` als letzten eigenen Middlewareeintrag. Bestehende eigene Middlewares bleiben dazwischen. Boundary erfasst die geprüfte Eingangsorigin; Terminal verifiziert das endgültige Ziel und startet im Enforce-Modus einen kontrollierten Transfer statt den automatisch gewählten Leafhandler.

Originwechsel durch dazwischenliegende Middleware werden abgelehnt. Die Boundary prüft die finale Response nach den eigenen Response-Middlewares und vor Guzzles Redirect-Verarbeitung. Damit wird auch eine nachträglich geänderte Location erfasst. Reguläre Redirects laufen erneut durch den Stack.

Reihenfolge und Einmaligkeit sind Invarianten. Ein bestehender `HandlerStack` als Globalkonfiguration oder ein Eintrag hinter Terminal ist nicht still kompatibel. AP-01 muss den konkreten Registrierungsweg im echten Bootstrap beider TYPO3-Versionen belegen; der Entwurf behauptet kein passendes, ungeprüftes Core-Event.

## Verworfene Alternativen

**Eine reine Vorprüfungsmiddleware:** unkontrollierter Transport. **Interne Corefactory dekorieren/Xclass:** unnötige Abhängigkeit von interner API. **Ganzen Client ersetzen:** verliert vorhandene Middlewaresemantik. **Ein terminaler Guard ohne Boundary:** kann spät geänderte Redirectantworten nicht kontrollieren.

## Konsequenzen

Der dokumentierte Erweiterungspunkt bleibt der Einstieg; die Transportgarantie ist stärker als ein Hostfilter. Die Lösung ist bewusst nicht vollständig transparent: feste Reihenfolge, kein beliebiger Leafhandler und nachzuweisende Bootstrapkompatibilität. Das ist das größte technische Freigabegate.

## Nachweis und Abnahme

T001, T002, T003, T004, T048, T083. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Scheitert die Integrationsprobe, wird dieser ADR ersetzt. Eine unkontrollierte `$next`-Delegation darf nicht als gleichwertige Ersatzlösung freigegeben werden.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
