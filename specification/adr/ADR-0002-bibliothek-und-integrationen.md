# ADR-0002: Gemeinsame Bibliothek statt Vault-Abhängigkeit aller Extensions

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-006, HG-036, HG-037, HG-041, HG-045  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

nr-vault besitzt bereits Zielprüfung, DNS-Pinning und einen eigenen Handlerstack. Daneben erfüllt es Aufgaben, die nicht zu einem allgemeinen Netzwerkschutz gehören: Secretzugriff, Credential-Injection, Audit, OAuth sowie spezialisierte Streaming-/Cancellation-APIs. [S04-S08, S16]

## Entscheidung

Vorgeschlagene Aufteilung: `netresearch/http-guard` als eigenständige PHP-Bibliothek und `netresearch/nr-http-guard` als TYPO3-Integration. nr-vault konsumiert die Bibliothek über einen eigenen Adapter. Die Bibliothek bekommt Konfiguration, Resolver, Clock und Reporter injiziert; sie liest keine TYPO3-Globals und kennt keine Vault-Datenbank.

Extrahiert werden Sicherheitsmechanismen und zugehörige Regressionen, nicht ungeprüft jede historische Ausnahme. Vault behält seine credentialtragenden Schnittstellen und Fachverantwortung. Das Librarypaket bietet keinen Secret-Export.

Bei Übernahme bestehenden Vault-Codes bleiben dessen Copyright- und SPDX-Hinweise erhalten. Als kompatibler Projektvorschlag verwenden beide neuen Pakete `GPL-2.0-or-later`; eine anders lizenzierte Veröffentlichung braucht vorher eine gesonderte Rechteklärung. Dieser ADR erteilt keine neuen Rechte.

## Verworfene Alternativen

**Jede Extension hängt von nr-vault ab:** unnötige Fachkopplung. **Dritte Kopie derselben Methoden:** Drift bei Securityfixes. **Kompletter Clientwechsel des Vaults:** gefährdet bewährte Auth-/Streamingsemantik. **Eigenständige Bibliothek erst später:** verfestigt erneut falsche Abhängigkeiten.

## Konsequenzen

Ein Sicherheitsfix erreicht alle integrierten Pfade über denselben Korpus. Es entstehen zwei neue Paketoberflächen und koordinierter Releasebedarf. Abstraktionen bleiben auf die tatsächlichen zwei Integrationen begrenzt; kein allgemeines HTTP-Framework.

## Nachweis und Abnahme

T007, T069, T070, T072, T076, T080. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Ein dritter Konsument kann zusätzliche Adapter motivieren. Eine Erweiterung der Bibliothek um Credentialverwaltung braucht eine neue Entscheidung und ist nicht implizit erlaubt.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
