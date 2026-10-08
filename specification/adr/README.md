# Architekturentscheidungen: Index

Stand: 2026-10-08. **Alle 14 ADRs sind vorgeschlagen.** Sie dokumentieren den vollständigen Sollentwurf, nicht bereits erteilte organisatorische Freigaben. Ihre Nummerierung ist unabhängig von den bestehenden ADR-Nummern in nr-vault.

Die zugrunde liegenden Quellen sind in [08 Evidenz und Quellen](../specs/08-evidence-and-sources.md) versioniert. Die folgenden Entscheidungen ergänzen sich; insbesondere bilden ADR-0003, ADR-0005 und ADR-0010 gemeinsam die Transportgarantie.

| ADR | Entscheidung | Status |
|---|---|---|
| [ADR-0001](ADR-0001-schutzumfang-und-vertrauensgrenzen.md) | Schutz des ausgehenden HTTP-Pfads, keine PHP-Firewall | Vorgeschlagen |
| [ADR-0002](ADR-0002-bibliothek-und-integrationen.md) | Gemeinsame Bibliothek statt Vault-Abhängigkeit aller Extensions | Vorgeschlagen |
| [ADR-0003](ADR-0003-middleware-und-kontrollierter-transport.md) | Zwei Middlewaregrenzen und ein kontrollierter terminaler Transport | Vorgeschlagen |
| [ADR-0004](ADR-0004-fail-closed-und-betriebsmodi.md) | Enforce als Standard, Observe nur als sichtbarer Migrationsmodus | Vorgeschlagen |
| [ADR-0005](ADR-0005-dns-und-connection-plan.md) | Geprüfte Adressmenge unmittelbar an die Verbindung binden | Vorgeschlagen |
| [ADR-0006](ADR-0006-clientgebundene-endpoint-grants.md) | Interne Zugriffe als clientgebundene Endpoint-Grants | Vorgeschlagen |
| [ADR-0007](ADR-0007-unterstuetzte-transporte.md) | Nur kontrolliertes cURL; Proxies und PHP-Streams nicht in v1 | Vorgeschlagen |
| [ADR-0008](ADR-0008-redirects-und-credential-grenzen.md) | Redirects pro Hop prüfen und Credentials an ihre Origin binden | Vorgeschlagen |
| [ADR-0009](ADR-0009-normalisierung-und-adressklassen.md) | Ein kanonischer Zielparser und ein versionierter Adresskorpus | Vorgeschlagen |
| [ADR-0010](ADR-0010-cache-isolation-und-parallelitaet.md) | Adressmemoisierung erlauben, Berechtigungs- und Verbindungspooling isolieren | Vorgeschlagen |
| [ADR-0011](ADR-0011-vault-streaming-und-cancellation.md) | Vault-Streaming erhalten, ohne zum PHP-Streamhandler auszuweichen | Vorgeschlagen |
| [ADR-0012](ADR-0012-fehler-und-beobachtbarkeit.md) | Stabile Ablehnungsgründe ohne neue Secret-Leaks | Vorgeschlagen |
| [ADR-0013](ADR-0013-kompatibilitaet-und-migration.md) | Explizite Migration statt stiller Umdeutung bestehender Allowlisten | Vorgeschlagen |
| [ADR-0014](ADR-0014-sicherheitsnachweis-und-release-gates.md) | Sicherheit durch Zielkontakt- und Regressionsevidenz abnehmen | Vorgeschlagen |

## Freigabeverfahren

Zuerst AP-01 und die Sicherheitsinvarianten prüfen. Anschließend Architektur, Security und Betreiberanforderungen gemeinsam freigeben. Annahmen, deren Integrationsnachweis fehlt, werden nicht allein durch Statuswechsel zu bewiesenen Eigenschaften.

Ein beschlossener ADR wird nicht nachträglich umgeschrieben, um eine andere Entscheidung historisch erscheinen zu lassen. Eine spätere Entscheidung erhält einen neuen ADR mit explizitem Verweis auf den abgelösten. Quellcodebefunde aus nr-vault behalten ihren eigenen historischen Status; dieses Paket ändert ihn nicht.

[Zurück zum Paketüberblick](../README.md).
