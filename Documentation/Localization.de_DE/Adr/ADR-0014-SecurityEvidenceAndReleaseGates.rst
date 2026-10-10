.. _adr-0014:

=======================================================================
ADR-0014: Sicherheit durch Zielkontakt- und Regressionsevidenz abnehmen
=======================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-039, HG-040, HG-041, HG-044, HG-045
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0014 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0014-sicherheitsnachweis-und-release-gates.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0014-context:

Kontext
=======

Ein Test kann grün sein, obwohl er nur eine Exception nach bereits versandtem
Request beobachtet. DNSrebinding, Redirects und geteilte Pools sind mit reinen
Unitmocks nicht ausreichend belegt. Bestehende Vault-ADRs unterscheiden bereits
Transport-nicht-erreicht-Tests und echte Wire-Nachweise. [S06, S08, S19]

.. _adr-0014-decision:

Entscheidung
============

Der normative Korpus enthält 84 Testfälle und ordnet alle 45 Anforderungen zu.
Jeder kritische Ablehnungsfall braucht einen negativen Zielkontaktbeweis: zuerst
Transportspy, für Transportinvarianten zusätzlich instrumentierte, hermetische
Zielserver bzw. Netznachweis. Die Testumgebung darf keine realen Cloudmetadaten-
oder fremden internen Dienste ansprechen.

Testschichten: Unit/Property, Libraryintegration, echte TYPO3-Registrierung,
echte cURL-Netztests, Concurrency/Worker sowie
Vaultnormal-/OAuth-/Streaming-/Cancellationpfade. Grenzfälle verwenden denselben
Korpus. Isolierte Netze und injizierte Resolver machen Antworten reproduzierbar;
keine Internetabhängigkeit für Freigabetests.

Gezielte Mutationen entfernen z.B. Pin oder Adressprüfung; die zugehörigen Tests
müssen dann scheitern. Release-Gates verlangen erst Integrationsprobe, dann
vollständigen P0-Nachweis und unabhängiges Securityreview. Ein neuer
Dependencymajor oder Sicherheitsfix startet die relevanten Gates erneut.

.. _adr-0014-rejected-alternatives:

Verworfene Alternativen
=======================

**Nur Coverageprozent:** sagt nichts über die behauptete Invariante. **Nur
Exceptions prüfen:** kann zu spät sein. **Nur manuelle Test-URL:** nicht
reproduzierbar und potenziell gefährlich. **Vorhandene ADR-Messungen als neue
Evidenz ausgeben:** verwechselt Quelle und ausgeführte Verifikation.

.. _adr-0014-consequences:

Konsequenzen
============

Die Abnahme ist auf konkrete Garantien zurückführbar. Ein Testharness und
mehrere Laufzeitkombinationen kosten Aufwand, verhindern aber unbemerkte
Regressionen. Dieses Dokumentationspaket allein erfüllt diese Produktgates noch
nicht.

.. _adr-0014-verification-and-acceptance:

Nachweis und Abnahme
====================

T001-T084; besonders T029-T033, T043-T048, T053-T061, T074-T080. Details und
erwartete Ergebnisse stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert,
nicht im Rahmen dieser Dokumentlieferung ausgeführt.

.. _adr-0014-reassessment-trigger:

Anlass für Neubewertung
=======================

Neue Angriffswege, Transportoptionen oder Konsumenten erweitern den Korpus vor
ihrer Freigabe. Ein nicht reproduzierbares Sicherheitsversprechen muss
eingeschränkt statt werblich aufrechterhalten werden.

.. _adr-0014-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
