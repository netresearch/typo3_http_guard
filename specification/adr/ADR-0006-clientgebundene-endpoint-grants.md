# ADR-0006: Interne Zugriffe als clientgebundene Endpoint-Grants

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-012, HG-017, HG-018, HG-019, HG-035, HG-038  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Interne ERP-, Such- oder LLM-Dienste sind legitime Ziele. Eine globale Hostfreigabe würde aber auch dem frei bedienbaren URL-Importer denselben Zugriff eröffnen. TYPO3-Kontexte liefern im bestehenden Factorycode keine automatisch an beliebige Middleware weitergereichte Autorisierung. Vaults flache Allowlist hat andere Semantik als die Core-Kontextliste. [S01, S03-S05]

## Entscheidung

Ein Profil bindet exakte Scheme-/Host-/Port-Origin, Methoden, zugelassene CIDRs, Zweck, Verantwortlichkeit und Ablauf. Die Anwendung erhält einen bereits gebundenen Client per vertrauenswürdiger Serviceverdrahtung. Eine bloße URL oder ein vom Benutzer gewählter Profilname aktiviert kein Grant.

Registry-eigene, nicht serialisierbare Grantobjekte sind an Profil und Policygeneration gebunden. Der interne ConnectionPlan ist nicht als dauerhafter Sendetoken exportierbar. Eine Diagnosefreigabe aus `policy-check` ist kein Grant.

Core-Allowlist, harte/global konfigurierte Verbote und Endpointprofil gelten kumulativ. Betreiber-Deny hat Vorrang. Private Netze brauchen enge CIDRs; Loopback zusätzlich ein ausdrückliches Flag und Hostpräfix. Metadaten-/Link-Local- und andere harte Verbote haben keinen pauschalen Allow-Schalter.

## Verworfene Alternativen

**Global `allow_private=true`:** zu breit. **Automatische Freigabe jedes konfigurierten Hostnamens:** löst das Confused-Deputy-Problem nicht. **Stringkontext als Geheimnis:** kopierbar und keine belastbare Bindung. **Prozessweites "aktueller Kontext":** fehleranfällig bei parallelen Requests.

## Konsequenzen

Interne Integrationen bleiben möglich, ohne Public-Fetch aufzuwerten. Sie erfordern ausdrückliche Clientinjektion. Grants sind eine Anwendungsarchitekturregel, keine harte Isolation gegen Codeausführung im selben PHP-Prozess.

## Nachweis und Abnahme

T021, T034, T035, T036, T037, T038, T039, T068, T073. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine neue Klasse interner Ziele oder flexiblere Profilsyntax braucht ein Threat-Model-Update. Wildcards und Netzbereichsfreigaben für alle Aufrufer sind kein beiläufiges Komfortfeature.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
