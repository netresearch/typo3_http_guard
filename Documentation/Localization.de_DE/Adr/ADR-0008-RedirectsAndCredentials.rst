.. _adr-0008:

========================================================================
ADR-0008: Redirects pro Hop prüfen und Credentials an ihre Origin binden
========================================================================

:Status: Historischer Vorschlag (Original: Vorgeschlagen)
:Datum: 2026-10-08
:Entscheidungsträger im Original: Projektverantwortliche Architektur/Security; Freigabe noch ausstehend
:Anforderungen: HG-017, HG-023, HG-024, HG-025
:Ablösung im Original: keine; neue Entscheidung für das vorgeschlagene Produkt
:Originalquelle: `Unveränderter ADR-0008 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/adr/ADR-0008-redirects-und-credential-grenzen.md>`_

.. note::

    Dieser ADR gibt den vollständigen Vorschlag vom 2026-10-08 wieder. Status,
    ausstehende Freigaben und geplante Tests beschreiben diesen Originalstand.
    Die aktuelle Alpha-Arbeit ist vom Nutzer freigegeben. Den heutigen
    Paketaufbau beschreibt :ref:`adr-0015`; für das aktuelle
    Sicherheitsverhalten und die ausgeführten Nachweise gelten :ref:`security`
    und :ref:`verification-report`.

.. _adr-0008-context:

Kontext
=======

Eine erlaubte Start-URL kann auf ein internes Ziel umleiten. Selbst bei
ausschließlich öffentlichen Zielen können Bodies oder eigene Authheader
vertrauliche Inhalte tragen. Eine generische Middleware kann diese Geheimnisse
nicht zuverlässig erkennen. Guzzle verarbeitet Redirects außerhalb der eigenen
Handler und PSR-18-Sends folgen nicht automatisch. [S01, S05, S15, S20]

.. _adr-0008-decision:

Entscheidung
============

Im generischen Client gilt Same-Origin. Jede verfolgte Location wird nach den
Response-Middlewares geprüft; der Folgeversuch braucht einen neuen
ConnectionPlan. Scheme, Host und effektiver Port bilden die Origin.
HTTPS-Downgrades sind verboten. Ein Profil kann Follow ganz ausschließen.

Ein gesonderter Public-Fetch-Client darf Cross-Origin folgen, aber nur als
GET/HEAD ohne Body, Cookies, Clientzertifikat, Autorisierung oder frei
eingespeiste Header. Dieser Client darf keine globalen Credentialdefaults erben.
Die Folgeadresse muss erneut zur Public-Policy passen.

Der effektive Follow-Modus und die Hopgrenze werden gegen die Betreiberpolicy
validiert. Ein zu großes bereits vom äußeren Guzzle-Redirectcode erfasstes Limit
wird abgelehnt, nicht durch eine wirkungslose innere Optionsänderung scheinbar
begrenzt. Eigene Wrapper setzen korrekte Limits bei Client-/Requestaufbau.
:literal:`allow_redirects=false` und PSR-18 geben 30x unverändert zurück.

Retrylogik bleibt beim Aufrufer; jeder echte neue Versuch wird erneut
autorisiert. Policyfehler sind kein Anlass für automatische Retries.

.. _adr-0008-rejected-alternatives:

Verworfene Alternativen
=======================

**Nur private Redirectziele sperren:** verhindert keine Credentialweitergabe an
öffentliche Angreiferziele. **Bekannte Authheader entfernen:** übersieht eigene
Header und Bodydaten. **Alle Redirects sperren:** unnötig für
Same-Origin/Public-Fetch. **Inneres Clamp ohne Wirkung auf äußeren
Redirectzustand:** falsches Sicherheitsversprechen.

.. _adr-0008-consequences:

Konsequenzen
============

Generische Requests mit legitimen Cross-Origin-Redirects können brechen. Die
sichere Sonder-API hat bewusst eine kleine Eingabeoberfläche. Methodenwechsel
und Hoplimits müssen pro Major konkret getestet werden.

.. _adr-0008-verification-and-acceptance:

Nachweis und Abnahme
====================

T045, T046, T047, T048, T049, T050, T051, T052, T081. Details und erwartete
Ergebnisse stehen in `06 Tests und Abnahme <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/06-verification.md>`_. Diese Tests sind spezifiziert, nicht im
Rahmen dieser Dokumentlieferung ausgeführt.

.. _adr-0008-reassessment-trigger:

Anlass für Neubewertung
=======================

Eine API für credentialtragende Cross-Origin-Redirects wäre ein neues
Autorisierungsmodell und braucht explizite Ziel-/Credentialfreigaben sowie einen
eigenen ADR.

.. _adr-0008-related-documents:

Zugehörige Dokumente
====================

`Produktanforderungen <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/01-product-requirements.md>`_, `Sicherheitsmodell <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/02-security-model.md>`_, `Architektur <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/03-architecture.md>`_, `Quellen S01-S23 <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/specs/08-evidence-and-sources.md>`_,
:ref:`ADR-Index <adr-index>`.
