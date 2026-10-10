.. _adr-0012:

=========================================================
ADR-0012: Stabile Ablehnungsgründe ohne neue Secret-Leaks
=========================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-031, HG-032, HG-033, HG-034, HG-035
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0012 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0012-fehler-und-beobachtbarkeit.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0012-context:

Kontext
=======

Ein Sicherheitsguard braucht Diagnose, kann aber durch Logs selbst Credentials
offenlegen. URLs können Querytokens tragen; Requests enthalten Bodies, Cookies
und Authheader. Ein nach Versand ausgeführter Statistikcallback ist außerdem
keine präventive Verbindungskontrolle. [S04, S08, S15]

.. _adr-0012-decision:

Entscheidung
============

Die Bibliothek liefert feste Reason-Codes mit typisierter Exceptionoberfläche.
Synchrone und Promise-basierte Aufrufe unterscheiden sich nicht in der
Policyentscheidung. Policyfehler werden nicht als normale Netzwerktimeouts
getarnt. PSR-18 erkennt sie als Clientfehler; der enthaltene Request bleibt
gegebenenfalls ein sensitives Objekt.

Telemetrie enthält Modus, Entscheidung, Profil-/Policykennung, Adressklasse,
Scheme/Port, Resolverquelle und Korrelation. Keine Bodies, Headerwerte,
URLpfade, Queries oder kompletten Exceptions. Hostanzeige ist separat
konfiguriert; pseudonyme Hostwerte verwenden einen Betreiber-HMAC-Schlüssel,
keinen öffentlich erratbaren Hash als angebliche Anonymisierung. Metriklabels
bleiben niedrigkardinal.

Loggerfehler dürfen nie aus Deny ein Allow machen. Rate-Limits reduzieren
Logfluten, nicht Denialzähler. Diagnosekommandos senden keine
Probe-HTTP-Requests. :literal:`on_stats` darf eine Abweichung nachträglich melden,
ersetzt aber niemals den Plan/Pinschutz.

.. _adr-0012-rejected-alternatives:

Verworfene Alternativen
=======================

**Vollständige Requests zur Fehlersuche loggen:** schafft Exfiltrationspfad.
**Jede Ablehnung als Angriff etikettieren:** verwechselt Tippfehler, DNSprobleme
und Missbrauch. **Logausfall generell als HTTP-Deny:** unnötige globale
Verfügbarkeitskopplung; Vaultaudit kann separat strenger sein.

.. _adr-0012-consequences:

Konsequenzen
============

Betrieb kann Ursachen erkennen, ohne Geheimnisse offenzulegen. Tiefere
Fehlersuche erfordert gezielte synthetische Reproduktion. Andere
Projektmiddlewares bleiben für ihre eigenen Logs verantwortlich; der Guard kann
deren Telemetrie nicht rückwirkend bereinigen.

.. _adr-0012-verification-and-acceptance:

Nachweis und Abnahme
====================

T062, T063, T064, T065, T067, T068. Details und erwartete Ergebnisse stehen in
`06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert, nicht im Rahmen dieser
Dokumentlieferung ausgeführt.

.. _adr-0012-reassessment-trigger:

Anlass für Neubewertung
=======================

Neue Telemetriefelder werden vor Aufnahme auf Geheimnisgehalt und Kardinalität
geprüft. Ein kompletter Requestdump ist kein zulässiger Debugkomfort.

.. _adr-0012-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
