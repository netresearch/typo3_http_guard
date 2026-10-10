.. _adr-0003:

============================================================================
ADR-0003: Zwei Middlewaregrenzen und ein kontrollierter terminaler Transport
============================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-001, HG-002, HG-009, HG-019, HG-021, HG-023
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0003 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0003-middleware-und-kontrollierter-transport.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

    :ref:`adr-0016` beschreibt die zusätzliche öffentliche RequestFactory-Grenze
    des umgesetzten Adapters. Die im Original verworfene Alternative bezieht
    sich auf die interne Client-Factory.

.. _adr-0003-context:

Kontext
=======

Der Core installiert eigene Middlewarehandler nach Guzzles Defaults und der
optionalen Core-Allowlist. Eine gewöhnliche Vorprüfung vor einem opaken
:literal:`$next` garantiert nicht, welcher Transport danach läuft. Auch ein
nachträgliches Umschreiben von Request-Origin oder Response-Location kann eine
frühere Prüfung entwerten. [S01, S05, S12, S20]

.. _adr-0003-decision:

Entscheidung
============

Der TYPO3-Adapter registriert :literal:`nr/http-guard-boundary` als ersten und :literal:`nr/http-guard-terminal`
als letzten eigenen Middlewareeintrag. Bestehende eigene Middlewares bleiben
dazwischen. Boundary erfasst die geprüfte Eingangsorigin; Terminal verifiziert
das endgültige Ziel und startet im Enforce-Modus einen kontrollierten Transfer
statt den automatisch gewählten Leafhandler.

Originwechsel durch dazwischenliegende Middleware werden abgelehnt. Die Boundary
prüft die finale Response nach den eigenen Response-Middlewares und vor Guzzles
Redirect-Verarbeitung. Damit wird auch eine nachträglich geänderte Location
erfasst. Reguläre Redirects laufen erneut durch den Stack.

Reihenfolge und Einmaligkeit sind Invarianten. Ein bestehender :literal:`HandlerStack`
als Globalkonfiguration oder ein Eintrag hinter Terminal ist nicht still
kompatibel. AP-01 muss den konkreten Registrierungsweg im echten Bootstrap
beider TYPO3-Versionen belegen; der Entwurf behauptet kein passendes,
ungeprüftes Core-Event.

.. _adr-0003-rejected-alternatives:

Verworfene Alternativen
=======================

**Eine reine Vorprüfungsmiddleware:** unkontrollierter Transport. **Interne
Corefactory dekorieren/Xclass:** unnötige Abhängigkeit von interner API.
**Ganzen Client ersetzen:** verliert vorhandene Middlewaresemantik. **Ein
terminaler Guard ohne Boundary:** kann spät geänderte Redirectantworten nicht
kontrollieren.

.. _adr-0003-consequences:

Konsequenzen
============

Der dokumentierte Erweiterungspunkt bleibt der Einstieg; die Transportgarantie
ist stärker als ein Hostfilter. Die Lösung ist bewusst nicht vollständig
transparent: feste Reihenfolge, kein beliebiger Leafhandler und nachzuweisende
Bootstrapkompatibilität. Das ist das größte technische Freigabegate.

.. _adr-0003-verification-and-acceptance:

Nachweis und Abnahme
====================

T001, T002, T003, T004, T048, T083. Details und erwartete Ergebnisse stehen in
`06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert, nicht im Rahmen dieser
Dokumentlieferung ausgeführt.

.. _adr-0003-reassessment-trigger:

Anlass für Neubewertung
=======================

Scheitert die Integrationsprobe, wird dieser ADR ersetzt. Eine unkontrollierte
:literal:`$next`-Delegation darf nicht als gleichwertige Ersatzlösung
freigegeben werden.

.. _adr-0003-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
