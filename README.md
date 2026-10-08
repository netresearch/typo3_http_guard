# HTTP Guard

Lokale Umsetzung des Plans „TYPO3 HTTP Client Schutz“ vom 8. Oktober 2026. Die Lieferung enthält die gemeinsame Bibliothek, eine TYPO3-Extension und den ausdrücklich zu aktivierenden nr-vault-Adapter. Interne Endpoints benötigen einen eigenen gebundenen Client; eine Hostliste oder statische DNS-Zuordnung erzeugt keine Freigabe.

| Bestandteil | Einstieg |
|---|---|
| Frameworkfreie Bibliothek, MIT | [packages/http-guard](packages/http-guard/) und [Policyvertrag](packages/http-guard/Documentation/Policy.md) |
| TYPO3-Extension, GPL-2.0-or-later | [Installation und erfasste Aufrufpfade](packages/nr-http-guard/README.md) |
| Optionaler Vault-Adapter | [Patch für den festgehaltenen Ausgangscommit](integrations/nr-vault/README.md) |
| Deutsche Betriebsanleitung | [Installation, Einführung und Rollback](docs/Installation-und-Betrieb.md) |
| Nachweise und Freigabestand | [Prüfbericht](docs/Pruefbericht.md) und [Anforderungszuordnung](verification/requirements-and-tests.md) |
| Wiederholbare Prüfungen | [Verification](verification/README.md) |
| Unveränderte Ausgangsspezifikation | [Specification](specification/README.md) |

Die tatsächlichen TYPO3-Laufzeiten sind 13.4.35 und 14.3.7, jeweils mit Guzzle 7.15.5 oder 8.2.0. Die Bibliothek sperrt unbekannte Kombinationen aus Guzzle, Promises und PSR-7 vor dem Versand. Proxybetrieb, PHP-Stream-Transport und HTTP/3 gehören nicht zum implementierten Umfang.

Die Lieferung ist ein unveröffentlichter Stand zur Prüfung. Die menschliche Sicherheitsprüfung gemäß G6 und der Pilotbetrieb an einer Betreiberinstanz stehen aus. Die Auditbefunde der bestehenden TYPO3-/Vault-Abhängigkeit `enshrined/svg-sanitize` sind im Prüfbericht festgehalten; Ausnahmen aus den isolierten Testinstanzen dürfen nicht in eine Produktionskonfiguration übernommen werden.
