.. _adr-0011:

===========================================================================
ADR-0011: Vault-Streaming erhalten, ohne zum PHP-Streamhandler auszuweichen
===========================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-028, HG-029, HG-030, HG-036, HG-037
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0011 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0011-vault-streaming-und-cancellation.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`. Der hier beschriebene nr-vault-Adapter ist eine
    historische Referenzintegration; seine Bereitstellung ist kein Bestandteil
    dieses Extension-Pakets.

.. _adr-0011-context:

Kontext
=======

Vaults ADR-039 beschreibt Streaming über einen gepinnten curl-multi-Transfer,
dessen Fortschritt beim Lesen des Responsebodys getrieben wird. :literal:`stream=true`
würde dagegen den PHP-Streamhandler auswählen und den Pin verlieren. Die
credentialtragende API kapselt Transport, Promise und Secrets bewusst. [S05,
S08]

.. _adr-0011-decision:

Entscheidung
============

Die globale Middleware unterstützt in v1 normale gebufferte Sends und lehnt
Guzzles :literal:`stream=true` explizit ab. Das schließt Vaults gesonderte
Streaming-API nicht aus. Deren Adapter verwendet dieselbe
Policy-/ConnectionPlanlogik, erhält aber eine intern kontrollierte Transferlease
für laufenden Fortschritt und Cancellation.

Ein einziger Owner verantwortet Tickschleife, Settlement und Freigabe.
Vorab-Cancellation verhindert den Start; Cancellation im Flug schließt den
aktiven Socket. Body-close, Fehler und abgebrochener Konsum geben Ressourcen
idempotent frei. Fehler nach Teilantworten werden nicht als vollständiger
EOF-Erfolg ausgegeben.

Vault behält Credential-Injection, vorherige Secretberechtigungen, Audit,
Buffergrenzen, Idle-/Gesamtzeitsemantik und die Unterscheidung zwischen Proxy-
und Originantworten in seinen bestehenden Regressionen. Die neue v1-Proxygrenze
wird als explizite Kompatibilitätsänderung behandelt, nicht versteckt. Seine
öffentliche API exportiert weiterhin keine rohen Securityoptionen.

.. _adr-0011-rejected-alternatives:

Verworfene Alternativen
=======================

**Streaming ganz streichen:** unnötiger Produktverlust. :literal:`stream=true` **akzeptieren:** falscher Transport. **Streamingmechanismus neu kopieren:**
vermeidbare Fehler bei EOF, Bufferlimits und Cancellation. **Rohen Client
herausgeben:** vergrößert die credentialtragende Oberfläche.

.. _adr-0011-consequences:

Konsequenzen
============

Die anspruchsvolle Vaultfunktion bleibt erhalten. Die Migration verlangt reale
Streaming-/Cancellationtests und ist nicht allein durch das Austauschen einer
Factory abgeschlossen. Lange Streams bleiben ressourcenbehaftete, explizit zu
beendende Transfers.

.. _adr-0011-verification-and-acceptance:

Nachweis und Abnahme
====================

T058, T059, T060, T061, T069, T070, T071, T072. Details und erwartete Ergebnisse
stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert, nicht im Rahmen
dieser Dokumentlieferung ausgeführt.

.. _adr-0011-reassessment-trigger:

Anlass für Neubewertung
=======================

Globales inkrementelles Streaming ist ein späteres Feature. Es muss dieselben
Pins, Lifecycle- und Fehlerverpflichtungen erfüllen, statt nur einen anderen
Guzzle-Schalter freizugeben.

.. _adr-0011-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
