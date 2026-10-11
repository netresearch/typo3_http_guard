.. _adr-0006:

==============================================================
ADR-0006: Interne Zugriffe als clientgebundene Endpoint-Grants
==============================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-012, HG-017, HG-018, HG-019, HG-035, HG-038
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0006 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0006-clientgebundene-endpoint-grants.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0006-context:

Kontext
=======

Interne ERP-, Such- oder LLM-Dienste sind legitime Ziele. Eine globale
Hostfreigabe würde aber auch dem frei bedienbaren URL-Importer denselben Zugriff
eröffnen. TYPO3-Kontexte liefern im bestehenden Factorycode keine automatisch an
beliebige Middleware weitergereichte Autorisierung. Vaults flache Allowlist hat
andere Semantik als die Core-Kontextliste. [S01, S03-S05]

.. _adr-0006-decision:

Entscheidung
============

Ein Profil bindet exakte Scheme-/Host-/Port-Origin, Methoden, zugelassene CIDRs,
Zweck, Verantwortlichkeit und Ablauf. Die Anwendung erhält einen bereits
gebundenen Client per vertrauenswürdiger Serviceverdrahtung. Eine bloße URL oder
ein vom Benutzer gewählter Profilname aktiviert kein Grant.

Registry-eigene, nicht serialisierbare Grantobjekte sind an Profil und
Policygeneration gebunden. Der interne ConnectionPlan ist nicht als dauerhafter
Sendetoken exportierbar. Eine Diagnosefreigabe aus :literal:`policy-check` ist kein
Grant.

Core-Allowlist, harte/global konfigurierte Verbote und Endpointprofil gelten
kumulativ. Betreiber-Deny hat Vorrang. Private Netze brauchen enge CIDRs;
Loopback zusätzlich ein ausdrückliches Flag und Hostpräfix.
Metadaten-/Link-Local- und andere harte Verbote haben keinen pauschalen
Allow-Schalter.

.. _adr-0006-rejected-alternatives:

Verworfene Alternativen
=======================

**Global** :literal:`allow_private=true`: zu breit. **Automatische Freigabe jedes
konfigurierten Hostnamens:** löst das Confused-Deputy-Problem nicht.
**Stringkontext als Geheimnis:** kopierbar und keine belastbare Bindung.
**Prozessweites "aktueller Kontext":** fehleranfällig bei parallelen Requests.

.. _adr-0006-consequences:

Konsequenzen
============

Interne Integrationen bleiben möglich, ohne Public-Fetch aufzuwerten. Sie
erfordern ausdrückliche Clientinjektion. Grants sind eine
Anwendungsarchitekturregel, keine harte Isolation gegen Codeausführung im selben
PHP-Prozess.

.. _adr-0006-verification-and-acceptance:

Nachweis und Abnahme
====================

T021, T034, T035, T036, T037, T038, T039, T068, T073. Details und erwartete
Ergebnisse stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert, nicht im
Rahmen dieser Dokumentlieferung ausgeführt.

.. _adr-0006-reassessment-trigger:

Anlass für Neubewertung
=======================

Eine neue Klasse interner Ziele oder flexiblere Profilsyntax braucht ein
Threat-Model-Update. Wildcards und Netzbereichsfreigaben für alle Aufrufer sind
kein beiläufiges Komfortfeature.

.. _adr-0006-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
