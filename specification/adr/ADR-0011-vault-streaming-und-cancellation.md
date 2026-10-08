# ADR-0011: Vault-Streaming erhalten, ohne zum PHP-Streamhandler auszuweichen

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-028, HG-029, HG-030, HG-036, HG-037  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Vaults ADR-039 beschreibt Streaming über einen gepinnten curl-multi-Transfer, dessen Fortschritt beim Lesen des Responsebodys getrieben wird. `stream=true` würde dagegen den PHP-Streamhandler auswählen und den Pin verlieren. Die credentialtragende API kapselt Transport, Promise und Secrets bewusst. [S05, S08]

## Entscheidung

Die globale Middleware unterstützt in v1 normale gebufferte Sends und lehnt Guzzles `stream=true` explizit ab. Das schließt Vaults gesonderte Streaming-API nicht aus. Deren Adapter verwendet dieselbe Policy-/ConnectionPlanlogik, erhält aber eine intern kontrollierte Transferlease für laufenden Fortschritt und Cancellation.

Ein einziger Owner verantwortet Tickschleife, Settlement und Freigabe. Vorab-Cancellation verhindert den Start; Cancellation im Flug schließt den aktiven Socket. Body-close, Fehler und abgebrochener Konsum geben Ressourcen idempotent frei. Fehler nach Teilantworten werden nicht als vollständiger EOF-Erfolg ausgegeben.

Vault behält Credential-Injection, vorherige Secretberechtigungen, Audit, Buffergrenzen, Idle-/Gesamtzeitsemantik und die Unterscheidung zwischen Proxy- und Originantworten in seinen bestehenden Regressionen. Die neue v1-Proxygrenze wird als explizite Kompatibilitätsänderung behandelt, nicht versteckt. Seine öffentliche API exportiert weiterhin keine rohen Securityoptionen.

## Verworfene Alternativen

**Streaming ganz streichen:** unnötiger Produktverlust. **`stream=true` akzeptieren:** falscher Transport. **Streamingmechanismus neu kopieren:** vermeidbare Fehler bei EOF, Bufferlimits und Cancellation. **Rohen Client herausgeben:** vergrößert die credentialtragende Oberfläche.

## Konsequenzen

Die anspruchsvolle Vaultfunktion bleibt erhalten. Die Migration verlangt reale Streaming-/Cancellationtests und ist nicht allein durch das Austauschen einer Factory abgeschlossen. Lange Streams bleiben ressourcenbehaftete, explizit zu beendende Transfers.

## Nachweis und Abnahme

T058, T059, T060, T061, T069, T070, T071, T072. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Globales inkrementelles Streaming ist ein späteres Feature. Es muss dieselben Pins, Lifecycle- und Fehlerverpflichtungen erfüllen, statt nur einen anderen Guzzle-Schalter freizugeben.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
