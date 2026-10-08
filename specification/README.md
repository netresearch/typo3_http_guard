# HTTP Guard: Spezifikation und Architekturentscheidungen

**Stand:** 8. Oktober 2026  
**Dokumentversion:** 0.1 - vollständiger Entwurf zur Implementierungsfreigabe  
**Status aller neuen ADRs:** Vorgeschlagen; nicht als bereits beschlossen oder umgesetzt zu verstehen.  
**Arbeitstitel:** HTTP Guard. Paketnamen in diesem Entwurf sind Vorschläge, keine Aussage über registrierte oder verfügbare Pakete.

## Entscheidung in einem Satz

Eine eigenständige TYPO3-Extension sichert den Standard-HTTP-Stack zentral ab; eine gemeinsam genutzte PHP-Bibliothek setzt Zielprüfung und verbindungsgebundenes DNS-Pinning durch. nr-vault verwendet dieselbe Bibliothek, bleibt aber verantwortlich für Secrets, Credential-Injection, Audit und seine Streaming-/Cancellation-APIs.

**Das Schutzversprechen gilt nur für erfasste Transportwege.** Die Lösung ist keine Firewall, keine PHP-Sandbox und keine Autorisierung für beliebige Ressourcen auf einem erlaubten Zielserver.

## Dokumente

| Dokument | Inhalt |
|---|---|
| [01 Produkt und Anforderungen](specs/01-product-requirements.md) | Ziele, Umfang, normative Anforderungen, Kompatibilität |
| [02 Sicherheitsmodell und Policies](specs/02-security-model.md) | Angreifer, Vertrauensgrenzen, IP-Klassen, Freigabesemantik |
| [03 Architektur und Laufzeit](specs/03-architecture.md) | Middleware, kontrollierter Transport, DNS, Redirects, Lebenszyklus |
| [04 Konfiguration und APIs](specs/04-configuration-and-api.md) | Vorgeschlagenes Schema, Defaults, Beispiele, Fehler, CLI |
| [05 nr-vault und Migration](specs/05-nr-vault-migration.md) | Quellcodebefund, Wiederverwendung, Unterschiede, BC-Plan |
| [06 Tests und Abnahme](specs/06-verification.md) | Testmatrix, Anforderungen-Tests-Zuordnung, Release-Gates |
| [07 Lieferung und Betrieb](specs/07-delivery-and-operations.md) | Arbeitspakete, Betriebsabläufe, Rollout und Rücknahme |
| [08 Evidenz und Quellen](specs/08-evidence-and-sources.md) | Untersuchte Quellen, Commit-Stände, Grenzen der Untersuchung |
| [ADR-Index](adr/README.md) | 14 einzeln begründete Architekturentscheidungen |

## Lesereihenfolge

Für die Freigabe: Dokumente 01, 02, 05 und ADR-Index. Für die Umsetzung: alle Dokumente; insbesondere die Transport- und Reihenfolgeinvarianten aus 03 und die Abnahme aus 06. Für den Betrieb: 04 und 07.

## Verbindlichkeit

**MUSS**, **DARF NICHT** und **SOLL** beschreiben Anforderungen des vorgeschlagenen Produkts, keine Eigenschaften einer bereits existierenden Extension. Bei Widersprüchen gilt: Sicherheitsinvarianten aus 02 vor Beispielen; normatives Schema aus 04 vor Kurzbeispielen; ADRs erklären Entscheidungen, ersetzen aber keine Anforderungen.

Geänderte Entscheidungen erhalten einen neuen ADR mit ausdrücklicher Ablösung des bisherigen. Sicherheitsversprechen dürfen nicht durch einen beiläufigen Konfigurationsschalter erweitert werden. Ungeprüfte Transportwege werden nicht als geschützt ausgewiesen.

## Lieferumfang und Grenzen dieses Entwurfs

Der Entwurf beruht auf einer gezielten Quellcode- und Dokumentationsprüfung, insbesondere von `netresearch/t3x-nr-vault` am Commit `5a070c396a614e5b05f63d79fa564c3748cf21eb`. Er ist keine vollständige Sicherheitsprüfung dieses Repositories. Es wurden weder eine neue Extension implementiert noch nr-vault-Tests oder Netzwerktests ausgeführt. Anforderungen und Tests unten sind die umzusetzenden bzw. zu erbringenden Nachweise.

Die technische Integrationsprobe in Arbeitspaket AP-01 ist ein Freigabegate: insbesondere letzte Middlewareposition, kontrollierter cURL-Transport, Guzzle-7/8-Verhalten und Isolation gleichzeitiger Transfers müssen vor der eigentlichen Implementierung bewiesen werden. Ein negatives Ergebnis verlangt eine ADR-Revision, keinen stillen Fallback.
