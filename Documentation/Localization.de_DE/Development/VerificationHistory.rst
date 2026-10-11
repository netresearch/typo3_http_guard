.. _verification-history:

====================================
Historische Nachweise und ihre Grenzen
====================================

.. _verification-original-report:

Unveränderter ursprünglicher Prüfbericht
=======================================

Der ursprüngliche
`Prüfbericht am Commit 7a3a39b
<https://github.com/netresearch/typo3_http_guard/blob/7a3a39bbaa763eb876ff4c9cdc743b689c557590/docs/Pruefbericht.md>`_
bleibt mit seinen Quellständen, Locks und Archivversionen erhalten. Die
vollständige englische Fassung im Handbuch bewahrt alle Messwerte und
Verweise. Das Verschieben oder Übersetzen des Berichts ist kein neuer
Testlauf.

Die damalige Ein-Paket-Matrix prüft zwölf PHP-/SDK-Zellen mit jeweils
126 Tests und 2.180 Assertions sowie 18 gezielte Sicherheitsmutanten.
Die echte Composer-Core-Matrix umfasst 168 Prozesse, 140 Bootstrap-Prüfungen
und 156 Offline-Nachweise ohne zusätzliche Kontakte. Die damaligen
klassischen Core-Archive 13.4.35 und 14.3.7 umfassen separat 84 Prozesse,
70 Bootstrap-Prüfungen und 78 Offline-Nachweise. Der damalige Extension-ZIP
enthält 106 Dateien. Seine erneute Installation liefert zusätzliche
70 Bootstrap-Prüfungen, die nicht zur 252-Prozess-Matrix gerechnet werden.
Diese Mengen schließen erwartete Guard-Ablehnungen ohne Zielkontakt ein;
die Bezeichnungen der unveränderten Originalberichte bleiben erhalten.

.. _verification-historical-evidence:

Frühere Quellstände
===================

Die Architekturprobe G0, der frühere Zwei-Paket-Kern und die vollständigen
Vault-Suiten behalten ihre jeweiligen Quellbindungen. Sie werden nicht
als erneut gegen den heutigen Ein-Paket-Quellstand ausgeführt dargestellt.
Die historischen Manifeste bewahren Originalpfade und Hashes; der Layoutplan
ordnet sie dem späteren Aufbau zu.

.. _verification-limits:

Messgrenzen und frühere Freigabeannahmen
======================================

Die frühere WSL-Mikrobenchmark verwendet 200 Stichproben, 64 IPv6-Adressen
und 128 Profile. p95 beträgt 1,455 ms ohne DNS, Netzwerk und Logging. Das
ist keine Messung auf der benannten CI-Referenz aus T079. Der separate
4-MiB-Sink-Nachweis prüft tatsächliches Streaming ohne zusätzlichen
Guard-Body-Puffer. Ein Ergebnis ersetzt das andere nicht.

Der damalige Bericht führte eine unabhängige menschliche Prüfung und
einen Betreiberpilot als noch offene Freigabeschritte auf. Für die
aktuelle, vom Nutzer autorisierte Alpha-Arbeit sind beide zurückgestellt
und keine zusätzlichen Freigabebedingungen. Keines von beiden wird als
durchgeführt dargestellt. Version 0.1.1 ist inzwischen auf TER und
Packagist veröffentlicht. Die historischen SVG-Befunde gehören zu den
alten Graphen und belegen keine aktuelle Produktionslücke; die aktuellen
Auditgrenzen stehen unter :ref:`dependency-report-current`.

Die historischen Linux-/PHP-/SDK-Läufe prüfen die tatsächlich ausgeführten
Versionen und Pfade. Sie qualifizieren keine beliebigen späteren Patches,
Hostingumgebungen oder unabhängigen SDK-Aufrufe. Semantische Kompatibilität
und Laufzeitprüfungen stehen unter :ref:`verification-semantic-support`.
