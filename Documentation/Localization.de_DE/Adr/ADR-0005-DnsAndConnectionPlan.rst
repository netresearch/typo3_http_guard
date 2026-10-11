.. _adr-0005:

===================================================================
ADR-0005: Geprüfte Adressmenge unmittelbar an die Verbindung binden
===================================================================

:Status: Vorschlag zur Standardauflösung durch :ref:`adr-0017` abgelöst
    (Original: Vorgeschlagen); Verbindungspinning bleibt gültig.
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-013, HG-014, HG-015, HG-016, HG-027, HG-044
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0005 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0005-dns-und-connection-plan.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

    :ref:`adr-0017` ersetzt den historischen Vorschlag zur Standardauflösung
    durch das umgesetzte DNS-Wire-Backend. Die ursprüngliche Prüfung der
    Adressmenge und das Verbindungspinning bleiben gültig.

.. _adr-0005-context:

Kontext
=======

Vorprüfung und unabhängige Transportauflösung können verschiedene IPs liefern.
Vault hat dies durch :literal:`CURLOPT_RESOLVE` adressiert und anschließend Leerantworten
geschlossen behandelt. Es behält jedoch eine Literal-Allowlist-Ausnahme bei. DNS
und NSS-/Hosts-Auflösung sind nicht gleichwertig. [S05-S07, S09, S19]

.. _adr-0005-decision:

Entscheidung
============

Jeder Versuch erzeugt nach Normalisierung und Policyprüfung einen
unveränderlichen ConnectionPlan. Alle verwertbaren A-/AAAA-Adressen werden
geprüft; ein verbotener Kandidat verwirft den gesamten Versuch. Keine brauchbare
Antwort bedeutet Ablehnung, auch bei einem freigegebenen Host. Statische
Hostzuordnungen liefern ebenfalls zu prüfende Adressen, keine Blankofreigabe.

Der Transport bekommt genau diese Menge in einem Multi-Address-Pin pro
Host-Port-Paar. Originalhostname, TLS-SNI und Zertifikatsprüfung bleiben
erhalten. Ein unerreichbarer Pin darf keinen ungeprüften DNSfallback auslösen.
Ungültige Records, Pinformate oder fehlschlagende Optionsetzer dürfen nicht
still ignoriert werden.

Auflösungsumfang und Memoisierung sind begrenzt. Ein synchrones :literal:`dns_get_record()`
wird nicht als hart unterbrechbarer Resolver ausgegeben; Betriebssystemgrenzen
und gemessenes Verhalten gehören zur Abnahme.

.. _adr-0005-rejected-alternatives:

Verworfene Alternativen
=======================

**Nur frisches DNS vor jedem Send:** bleibt ein Check-to-connect-Fenster. **Nur
gefährliche IPs herausfiltern:** verdeckt gemischte Vertrauenszonen und wird für
v1 abgelehnt. **Expliziter Host darf bei DNSfehler unkontrolliert weiter:**
verletzt die verbindungsgebundene Garantie. **URL durch IP ersetzen:** erschwert
korrekte Host-/TLS-Semantik.

.. _adr-0005-consequences:

Konsequenzen
============

Die Auswahl ist nachvollziehbar und testbar. Split-DNS-Konfigurationen mit
gemischten erlaubten/verbotenen Antworten werden bewusst verweigert. Nicht in
DNS bekannte Namen brauchen eine statische Zuordnung oder einen später
zertifizierten Resolveradapter.

.. _adr-0005-verification-and-acceptance:

Nachweis und Abnahme
====================

T022, T023, T024, T025, T026, T027, T028, T029, T030, T031, T032, T033, T078.
Details und erwartete Ergebnisse stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind
spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

.. _adr-0005-reassessment-trigger:

Anlass für Neubewertung
=======================

Weitere Resolver dürfen aufgenommen werden, wenn sie Kandidaten vollständig und
begrenzt liefern und niemals die Transportauflösung unkontrolliert freigeben.

.. _adr-0005-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
