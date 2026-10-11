.. _adr-0004:

==========================================================================
ADR-0004: Enforce als Standard, Observe nur als sichtbarer Migrationsmodus
==========================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-003, HG-004, HG-021, HG-033, HG-034, HG-043
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0004 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0004-fail-closed-und-betriebsmodi.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0004-context:

Kontext
=======

Eine neu installierte Securityextension erzeugt eine Schutzannahme. Ein stiller
Fallback ohne cURL oder bei ungültiger Policy würde diese Annahme verletzen.
Gleichzeitig braucht ein Bestandsprojekt eine kontrollierte Inventarisierung
seiner internen Verbindungen. Vault bietet heute absichtlich ein schwächeres
Verhalten ohne cURL. [S04-S06, S16]

.. _adr-0004-decision:

Entscheidung
============

Neue Installation: :literal:`enforce`, keine internen Grants. Fehlende optionale
Konfiguration verwendet sichere Defaults; ungültige vorhandene Konfiguration
scheitert. Nicht unterstützte Transportbedingungen blockieren vor Kontakt zum
Ziel oder Proxy.

:literal:`observe` ist eine explizite Betreiberentscheidung. Er protokolliert
soweit prüfbar :literal:`would_deny` bzw. :literal:`unverifiable`, delegiert aber an den
bisherigen Transport und darf nicht als geschützt gelten. Ein Diagnoseproblem
soll dort nicht als erfolgreiche Sicherheitsprüfung erscheinen. Vorhandene
Core-/Vaultkontrollen bleiben unangetastet.

:literal:`disabled` ist transparent und sichtbar. Es gibt keinen automatischen
Wechsel von Enforce zu Observe oder Disabled. Rollback und Ausnahmen benötigen
Änderung an vertrauenswürdiger Projektkonfiguration. Kein Schalter aus einem
HTTP-Parameter oder entfernten Responseheader.

.. _adr-0004-rejected-alternatives:

Verworfene Alternativen
=======================

**Observe als Installationsdefault:** liefert zunächst keinen erwarteten Schutz.
**Warnung statt Block bei Transportlücken:** schwer erkennbarer Verlust der
Invariante. **Fail-open bei Konfigurationsfehlern:** ein Tippfehler würde
Berechtigungen erweitern.

.. _adr-0004-consequences:

Konsequenzen
============

Fehler sind eindeutig; Bestandsinstallationen können aber nach Aktivierung
ausfallen, bis legitime interne Zugriffe erfasst sind. Der Rollout braucht
Inventar, Diagnose und bewusste Freigabe. Observe-Telemetrie ist keine Abnahme
des Enforce-Transports.

.. _adr-0004-verification-and-acceptance:

Nachweis und Abnahme
====================

T005, T006, T042, T065, T066, T067. Details und erwartete Ergebnisse stehen in
`06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert, nicht im Rahmen dieser
Dokumentlieferung ausgeführt.

.. _adr-0004-reassessment-trigger:

Anlass für Neubewertung
=======================

Eine Änderung des Installationsdefaults ist eine produktweite
Security-/Kompatibilitätsentscheidung. Automatische Herabstufung bleibt
unabhängig davon unzulässig.

.. _adr-0004-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
