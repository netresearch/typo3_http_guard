.. _adr-0009:

=======================================================================
ADR-0009: Ein kanonischer Zielparser und ein versionierter Adresskorpus
=======================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-007, HG-008, HG-009, HG-010, HG-011, HG-012
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0009 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0009-normalisierung-und-adressklassen.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0009-context:

Kontext
=======

Parser interpretieren nichtkanonische numerische Adressen unterschiedlich.
Hostheader und URI können voneinander abweichen. Ein grober Private-IP-Filter
deckt nicht alle lokalen oder speziellen Adressklassen ab. IANA führt
eigenständige IPv4-/IPv6-Spezialregister. [S04, S05, S10, S11]

.. _adr-0009-decision:

Entscheidung
============

Der Guard normalisiert einmal, verwendet danach ein typisiertes Target und
wendet dieselbe Authority auch beim Transport an. Absolute HTTP-/HTTPS-URLs sind
Pflicht; Userinfo, Fragmente, Steuerzeichen, Zone-IDs, Host-Prozentkodierung und
nichtkanonische numerische Formen werden abgewiesen. HTTP-Host und URI-Authority
müssen nach Normalisierung übereinstimmen. Unicodehostnamen werden in v1 nicht
implizit konvertiert; bereits kanonisch umgewandelte ASCII-IDNA-Namen sind
zulässig.

IP- und CIDR-Prüfungen erfolgen binär für beide Familien. IPv4-mapped IPv6 wird
auf die eingebettete IPv4-Policy zurückgeführt. Tunnel-/Übersetzungsbereiche
werden konservativ gesperrt. Der versionierte Korpus benennt Privaträume,
Loopback, Link-Local, Metadatenrelevanz, Multicast, Dokumentations-/Benchmark-
und weitere Spezialbereiche.

Die v1-Public-Policy ist bewusst konservativer als eine bloße positive
Globally-Reachable-Markierung einzelner Spezialadressen. Betreiber-Deny kann
zusätzlich auch nominell öffentliche, intern geroutete Bereiche sperren.
Registerupdates werden versioniert, nie bei jedem Request live heruntergeladen.

.. _adr-0009-rejected-alternatives:

Verworfene Alternativen
=======================

**Nur Regex:** ungeeignet als umfassende IP-/CIDRentscheidung. **Nur
PHP-Filterflags:** koppelt Policy unbemerkt an deren konkrete Laufzeitsemantik.
**PrivateIPv4 ohne IPv6:** lässt eine zweite Adressfamilie offen. **Jede
Parserkorrektur akzeptieren:** vergrößert Interpretationsunterschiede.

.. _adr-0009-consequences:

Konsequenzen
============

Es gibt einen testbaren Entscheidungsweg und nachvollziehbare
Datensatzversionen. Einzelne legitime Spezialadressen und Unicodeeingaben sind
eingeschränkt; Aufrufer müssen kanonische Ziele liefern. Öffentliche IP bedeutet
weiterhin nicht "inhaltlich vertrauenswürdiger Server".

.. _adr-0009-verification-and-acceptance:

Nachweis und Abnahme
====================

T008, T009, T010, T011, T012, T013, T014, T015, T016, T017, T018, T019, T020,
T021. Details und erwartete Ergebnisse stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests
sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

.. _adr-0009-reassessment-trigger:

Anlass für Neubewertung
=======================

Eine Lockerung einer gesperrten Adressklasse oder Parserform braucht
Korpusänderung, Begründung und Grenztests. Neue IANA-Einträge werden wie
sicherheitsrelevante Abhängigkeitsänderungen behandelt.

.. _adr-0009-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
