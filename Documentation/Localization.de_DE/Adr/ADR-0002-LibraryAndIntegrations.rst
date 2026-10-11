.. _adr-0002:

=========================================================================
ADR-0002: Gemeinsame Bibliothek statt Vault-Abhängigkeit aller Extensions
=========================================================================

:Status: Abgelöst durch ADR-0015 (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-006, HG-036, HG-037, HG-041, HG-045
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0002 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0002-bibliothek-und-integrationen.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`. ADR-0015 ersetzt die Paketaufteilung. Die
    ursprünglichen Gründe für getrennte Sicherheits- und Integrationsaufgaben
    bleiben als Kontext erhalten.

.. _adr-0002-context:

Kontext
=======

nr-vault besitzt bereits Zielprüfung, DNS-Pinning und einen eigenen
Handlerstack. Daneben erfüllt es Aufgaben, die nicht zu einem allgemeinen
Netzwerkschutz gehören: Secretzugriff, Credential-Injection, Audit, OAuth sowie
spezialisierte Streaming-/Cancellation-APIs. [S04-S08, S16]

.. _adr-0002-decision:

Entscheidung
============

Vorgeschlagene Aufteilung: :literal:`netresearch/http-guard` als eigenständige PHP-Bibliothek und
:literal:`netresearch/nr-http-guard` als TYPO3-Integration. nr-vault konsumiert die Bibliothek über
einen eigenen Adapter. Die Bibliothek bekommt Konfiguration, Resolver, Clock und
Reporter injiziert; sie liest keine TYPO3-Globals und kennt keine
Vault-Datenbank.

Extrahiert werden Sicherheitsmechanismen und zugehörige Regressionen, nicht
ungeprüft jede historische Ausnahme. Vault behält seine credentialtragenden
Schnittstellen und Fachverantwortung. Das Librarypaket bietet keinen
Secret-Export.

Bei Übernahme bestehenden Vault-Codes bleiben dessen Copyright- und
SPDX-Hinweise erhalten. Als kompatibler Projektvorschlag verwenden beide neuen
Pakete :literal:`GPL-2.0-or-later`; eine anders lizenzierte Veröffentlichung braucht vorher
eine gesonderte Rechteklärung. Dieser ADR erteilt keine neuen Rechte.

.. _adr-0002-rejected-alternatives:

Verworfene Alternativen
=======================

**Jede Extension hängt von nr-vault ab:** unnötige Fachkopplung. **Dritte Kopie
derselben Methoden:** Drift bei Securityfixes. **Kompletter Clientwechsel des
Vaults:** gefährdet bewährte Auth-/Streamingsemantik. **Eigenständige Bibliothek
erst später:** verfestigt erneut falsche Abhängigkeiten.

.. _adr-0002-consequences:

Konsequenzen
============

Ein Sicherheitsfix erreicht alle integrierten Pfade über denselben Korpus. Es
entstehen zwei neue Paketoberflächen und koordinierter Releasebedarf.
Abstraktionen bleiben auf die tatsächlichen zwei Integrationen begrenzt; kein
allgemeines HTTP-Framework.

.. _adr-0002-verification-and-acceptance:

Nachweis und Abnahme
====================

T007, T069, T070, T072, T076, T080. Details und erwartete Ergebnisse stehen in
`06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert, nicht im Rahmen dieser
Dokumentlieferung ausgeführt.

.. _adr-0002-reassessment-trigger:

Anlass für Neubewertung
=======================

Ein dritter Konsument kann zusätzliche Adapter motivieren. Eine Erweiterung der
Bibliothek um Credentialverwaltung braucht eine neue Entscheidung und ist nicht
implizit erlaubt.

.. _adr-0002-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
