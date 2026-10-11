.. _development-requirements:

========================================
Anforderungen und Sicherheitsinvarianten
========================================

Dieses Kapitel verbindet den ursprünglichen Entwurf mit dem heutigen
Ein-Extension-Paket. Die Originaltexte, Statusangaben und Messungen bleiben
in der unveränderten Git-Historie; diese Übersicht erklärt den aktuellen
Vertrag und Testaufbau. Historische IDs sind keine Behauptung, dass alle
84 alten Szenarien heute erneut bestanden wurden. Aktuelle Ausführungen
mit Quellenbindung stehen unter :ref:`verification-report` und
:ref:`assessment-reconciliation`.

Der Entwurf enthält acht Invarianten, 45 IDs HG-001–HG-045 und
84 Szenarien T001–T084 (74 P0, zehn P1). HG-006 entspricht heute einem
frameworkunabhängigen Kern innerhalb einer Extension; die aktualisierte
Verpackungsentscheidung steht unter :ref:`decision-single-extension`.

.. _development-requirements-invariants:

Aktueller Sicherheitsvertrag
============================

* **INV-01:** Jede Verbindung nutzt ausschließlich die geprüften IPs des
  aktuellen Plans.
* **INV-02:** Schema, Host, Port, Methode, Endpoint-Profil und Policyrevision
  binden den Plan; Änderungen machen ihn ungültig.
* **INV-03:** Ungültige Ziele, fehlende IPs oder unsichere Laufzeitbedingungen
  scheitern in enforce vor Kontakt.
* **INV-04:** Interne Freigaben benötigen einen ausdrücklich gebundenen Client
  und Endpoint.
* **INV-05:** Redirects und Retries werden erneut geprüft; eine URL ist keine
  dauerhafte Freigabe.
* **INV-06:** Policyregeln wirken gemeinsam; Sperren haben Vorrang vor
  Freigaben.
* **INV-07:** Freigaben, Pins und Verbindungen dürfen keine Request-, Client-
  oder Policygrenze überschreiten.
* **INV-08:** Ungeschützte Modi und nicht integrierte Pfade erhalten keine
  Schutzbehauptung.

.. _development-requirements-ids:

Übersicht der ursprünglichen IDs
================================

Kurzthemen und Zuordnung stammen aus dem unveränderten Anforderungsledger.
Die Spalte der Szenarien bewahrt dessen Zuordnung, ohne neue Statusangaben.

.. list-table:: Originale HG-/T-Zuordnung
    :header-rows: 1

    * - Anforderungs-ID und Kurzthema
      - Ursprüngliche Szenarien
    * - HG-001: Erweiterungspunkt für ausgehendes HTTP
      - T001
    * - HG-002: Guard vor jedem nativen Senden
      - T002, T003, T004, T083
    * - HG-003: Enforce und sichere Ablehnung als Standard
      - T005, T006
    * - HG-004: Keine impliziten internen Freigaben
      - T005
    * - HG-005: Getrennte unterstützte Core-Ziele
      - T001, T041, T082
    * - HG-006: Frameworkunabhängiger eingebetteter Kern
      - T007
    * - HG-007: Gemeinsame strenge URL-Normalisierung
      - T008, T010
    * - HG-008: Mehrdeutige oder fremde Protokolle ablehnen
      - T009, T010, T011, T012
    * - HG-009: Effektive Authority binden
      - T004, T013
    * - HG-010: Binäre Adressklassifikation
      - T014, T015, T016, T020
    * - HG-011: Spezialnetze sperren
      - T014, T015, T017, T018, T019, T020
    * - HG-012: Vorrang zusätzlicher CIDR-Sperren
      - T021
    * - HG-013: Jeden A-/AAAA-Kandidaten prüfen
      - T022, T023, T028
    * - HG-014: Verifizierte Auflösung verlangen
      - T024, T025, T026, T027
    * - HG-015: Nur geprüfte Verbindungspläne verwenden
      - T026, T027, T029, T030, T033, T084
    * - HG-016: Vollständige erlaubte Adressmenge pinnen
      - T030, T031, T032
    * - HG-017: Endpoint und Client ausdrücklich binden
      - T025, T027, T034, T035, T036, T037, T038, T081
    * - HG-018: Keine nutzergesteuerten Freigaben
      - T034, T035, T036
    * - HG-019: Core- und Endpoint-Regeln kumulieren
      - T002, T004, T021, T039, T083
    * - HG-020: Unsichere Transportoptionen ablehnen
      - T040, T041
    * - HG-021: Kontrollierte cURL-Fähigkeiten
      - T042
    * - HG-022: Proxyrouten erkennen und ablehnen
      - T043, T044
    * - HG-023: Jeden Redirect neu prüfen
      - T045, T046, T048, T049, T051, T081
    * - HG-024: Redirect-Credentials beschränken
      - T046, T047, T048, T050
    * - HG-025: Retries neu prüfen ohne implizites Budget
      - T052
    * - HG-026: DNS- und Verbindungscaches isolieren
      - T053, T054, T055, T084
    * - HG-027: Verifizierte Memoisierung begrenzen
      - T056, T057
    * - HG-028: Allgemeinen Streaming-Fallback ablehnen
      - T058
    * - HG-029: Streaming bewusst integrieren
      - T059, T060
    * - HG-030: Abbruch und Bereinigung
      - T060, T061
    * - HG-031: Typisierte synchrone und asynchrone Fehler
      - T062
    * - HG-032: Reduzierte begrenzte Protokollierung
      - T063, T064
    * - HG-033: Observe-/Disabled-Grenzen anzeigen
      - T065, T066
    * - HG-034: Offline-Diagnose
      - T067
    * - HG-035: URL-Prüfung erteilt keine wiederverwendbare Freigabe
      - T068
    * - HG-036: Vault ausdrücklich extern integrieren
      - T069, T070
    * - HG-037: Externe Vault-Semantik erhalten
      - T059, T060, T070, T071, T072
    * - HG-038: Alte Allowlists geben keinen globalen Zugriff
      - T039, T073
    * - HG-039: Tatsächliche Nullkontakt-Zeugen
      - T074
    * - HG-040: Parallelität und langlebige Worker isolieren
      - T053, T054, T075
    * - HG-041: Gemeinsamer Corpus mit klarem Integrationsumfang
      - T074, T076
    * - HG-042: Ungeschützte Pfade benennen
      - T003, T006, T077
    * - HG-043: Ausdrückliche versionierte Migration
      - T038, T066, T073, T080
    * - HG-044: Zeit- und Speichergrenzen dokumentieren
      - T028, T057, T078, T079, T082
    * - HG-045: Reproduzierbare qualifizierte Regressionen
      - T076, T080

.. _development-requirements-tests:

Heutige Test- und Build-Einstiegspunkte
=======================================

Die Gruppen decken alle ursprünglichen Szenario-IDs ab und zeigen heutige
Prüfstellen für dieselben Themen. Unit-, echte Core-, Wire-, Mutation- und
Performance-Ergebnisse bleiben getrennt; ein Dateiname belegt keinen Lauf.
Die folgenden kurzen Testnamen beziehen sich auf :file:`Tests/Unit/`,
:file:`Tests/HttpGuard/Unit/Policy/` beziehungsweise
:file:`Tests/HttpGuard/Unit/Transport/`; die Dateinamen enden auf .php.

* T001–T007: CoreStackProviderContractTest, RequestFactoryAbiContractTest,
  GuardConfigurationBoundaryContractTest; echte Core-Pfade unter
  :file:`Tests/Functional/`, Vorbereitung unter :file:`Build/Fixtures/`.
* T008–T021: TargetAuthorityBoundaryContractTest,
  CidrMembershipBoundaryContractTest und AddressRulesBoundaryContractTest.
* T022–T033: ResolverAnswerBoundaryContractTest und
  PolicyPlanBoundaryContractTest; tatsächliches DNS und Pinning unter
  :file:`Tests/HttpGuard/Integration/DnsPolicyTransportTest.php`.
* T034–T044: ClientFactoryContractTest, OptionSanitizerContractTest und
  MiddlewareRegistryContractTest; native Options-/Proxygrenzen unter
  :file:`Tests/HttpGuard/Integration/ProductionTransportTest.php`.
* T045–T057: InvocationLifecycleTest und ResolverResidualCacheContractTest;
  Redirects, Retries und Isolation in ProductionTransportTest.php.
* T058–T064: TransferLeaseLifecycleContractTest,
  TransferLeaseRuntimeBoundaryContractTest und DecisionReporterContractTest.
  Die Vault-Anteile T059–T061 behalten ihre externe historische Qualifikation.
* T065–T073: DiagnosticsContractTest, CommandContractTest,
  ManagedAuthenticationContractTest und :file:`Tests/Functional/mode-bootstrap.php`.
  T070–T072 benötigen Vault; Kerntests ersetzen dessen vollständige Suite nicht.
* T074–T077: Wire-No-Contact-/Parallelitätszeugen in den Integrationstests
  und versionierter Corpus unter :file:`Resources/Private/HttpGuard/data/`.
  T076s Vault-Teil bleibt historisch; T077 beschreibt nicht integrierte Pfade.
* T078–T084: WireDnsQueryTest, ManagedAuthenticationContractTest und
  CoreStackProviderContractTest; Policy-Benchmark unter
  :file:`Build/Scripts/benchmark-policy.php`, Mutation über
  :file:`infection.native.json5` und :file:`.github/workflows/verification.yml`.

T079s benannte CI-Policy-Messung steht unter :ref:`assessment-reconciliation`.
Sie ist keine End-to-End-HTTP-Messung. Historische gezielte Mutanten und
globale Infection-MSI-Werte sind getrennte Nachweise. Das optionale
Vault-Beispiel unter :ref:`api-vault` ist kein Bestandteil der produktiven
Extension und kein erneut ausgeführter Fremdprojekt-Release.

Gepflegte Berichte mit Quellenbezug liegen unter
:file:`Build/Reports/Assessment/completion/reconciliation.json`; aktuelle
Tests unter :file:`Tests/`, Werkzeuge unter :file:`Build/`. Diese
Entwicklungsdateien sind keine Runtime-Abhängigkeiten des TER-Pakets.

.. _development-requirements-sources:

Unveränderte historische Quellen
================================

Die 28 ursprünglichen Spezifikationsdateien und Rohbelege bleiben am
Commit :literal:`3f929ae8794ca04ab3dd25627e94d601a2db23e0` sowie im
geprüften externen Quellarchiv erhalten (SHA-256 unten). Dieses Handbuch
kopiert weder widersprüchliche alte API-Entwürfe noch historische Pass-Status.

* `Originalspezifikationen und vorgeschlagene ADRs
  <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/specification/README.md>`_
* `Originalledger mit vollständigen 45-/84-Zuordnungen
  <https://github.com/netresearch/typo3_http_guard/blob/3f929ae8794ca04ab3dd25627e94d601a2db23e0/verification/requirements-and-tests.md>`_

:literal:`2e417da29df5348e284a93f1db96b2a067426dc5ce4e7ee74f978375d6eb34c9`
