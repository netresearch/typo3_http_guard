# 08 - Evidenz, Quellen und Unsicherheiten

Abruf-/Prüfdatum: 8. Oktober 2026.

## 1. Untersuchte Stände

- nr-vault: `5a070c396a614e5b05f63d79fa564c3748cf21eb`, Commitdatum 2026-10-07; kein erfundener Releasebezug.
- TYPO3 Branch 14.3: aufgelöster Commit `cfa1b1cbf3022c19ba231731da397f5847f3c29a`; Factorydatei zusätzlich über ihren Blob identifiziert.
- Guzzle: beim gezielten Code-Search gefundener Stand `93939470950a9b11e2e84204166ef5e048c55fe4`. Die dort beschriebenen neuen APIs sind nicht ungeprüft jeder Guzzle-Installation zuzuordnen.
- TYPO3 13.4 ist ein verbindliches Implementierungs-/Testziel dieses Entwurfs, kein in diesem Dokument behaupteter vollständiger Branch-Audit.

## 2. Was geprüft wurde

Gezielte Quellcode- und ADR-Lektüre zur Architekturfrage. Core-Erweiterungspunkt, Kontextweitergabe, Vault-Handlerstack, Adress-/DNS-/Pinningregeln, Literal-Allowlist-Ausnahmen, degradierte Transportpfade, Streamingarchitektur und aktuelle Dependency-Majors wurden nachvollzogen. Daraus wurde ein eigenständiger Sollentwurf erstellt.

## 3. Was nicht nachgewiesen wurde

Keine neue Implementierung, kein vollständiger Vault-Sicherheitsaudit, keine automatisierte Repositoryanalyse, keine erneut ausgeführten Vault-Unit-/Wire-Tests, keine live ausgenutzte Schwachstelle. Eine Aussage aus einem bestehenden ADR ist nicht derselbe Nachweis wie ein hier ausgeführter Test.

Ein lokaler Repository-Clone und der dokumentierte Context7-CLI-Abruf waren in der Arbeitsumgebung mangels verfügbarer Netzauflösung nicht nutzbar. Die relevanten Dateien wurden über die GitHub-Verbindung gelesen; öffentliche technische Referenzen über Webabrufe geprüft. Daraus wird kein erfolgreicher Build oder Testlauf abgeleitet.

Die eigene Dokumentlieferung wird auf vorhandene Dateien, interne Verweise, eindeutige IDs und Anforderungs-Test-Zuordnung mechanisch geprüft. Das ist Dokument-QA, nicht Produkt-Sicherheitsverifikation.

## 4. Fakten und Entscheidungen auseinanderhalten

"nr-vault hat einen separaten Stack" ist ein Quellcodebefund. "Die neue Library soll isolierte Transfers benutzen" ist eine vorgeschlagene Architekturentscheidung. "Ein bestimmter Proxybetrieb ist verwundbar" wäre eine gesondert zu belegende Sicherheitsbehauptung und wird hier nicht pauschal erhoben.

Entscheidungen zum Umfang, zum strengen Ausnahmeformat, zu nicht unterstützten Proxies/Streams und zu isolierten Verbindungen sind bewusst konservativ. Sie sollten nur nach konkreten Testergebnissen geändert werden, nicht aus Gründen scheinbarer Transparenz oder geringeren Konfigurationsaufwands.

## 5. Quellenverzeichnis

### S01 - TYPO3 GuzzleClientFactory: Stackaufbau und Kontextallowlist

Quelle: https://github.com/TYPO3/typo3/blob/cfa1b1cbf3022c19ba231731da397f5847f3c29a/typo3/sysext/core/Classes/Http/Client/GuzzleClientFactory.php

Vollständige Datei gelesen; Blob 9a4ee2cb7c62d90cafeb9700604c02d7b8a3db65.

### S02 - Offizielle TYPO3-Dokumentation zum ausgehenden Middleware-Erweiterungspunkt

Quelle: https://docs.typo3.org/m/typo3/reference-coreapi/14.3/en-us/ExtensionArchitecture/HowTo/RestRequests/Index.html

Abschnitt Custom middleware handlers sowie RequestFactory-Verhalten gelesen.

### S03 - TYPO3 RequestFactory: request-Optionen und Kontext

Quelle: https://github.com/TYPO3/typo3/blob/cfa1b1cbf3022c19ba231731da397f5847f3c29a/typo3/sysext/core/Classes/Http/RequestFactory.php

Vollständige Datei gelesen; Kontext wird an getClient weitergegeben.

### S04 - nr-vault SecureHttpClientFactory: eigener Stack, Vorprüfung, Optionen

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Classes/Http/SecureHttpClientFactory.php

Gezielt create, createCancellable, isHostAllowed sowie Middleware und Pinaufbau gelesen; keine Behauptung einer vollständigen Repositoryprüfung.

### S05 - nr-vault SecureHttpClientFactory: Streamgrenze, Ausnahmen, Pinaufbau und Allowlist

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Classes/Http/SecureHttpClientFactory.php#L610-L960

Insbesondere Warnung ohne cURL, pinWillBeHonoured, buildResolveEntries und resolveAllowedHostsList geprüft.

### S06 - nr-vault ADR-038: ungeprüfte Auflösung wird abgelehnt

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Documentation/Developer/Adr/ADR-038-UnresolvableHostIsRefused.rst

Vollständig gelesen; beschreibt ausdrücklich die verbleibende Literal-Allowlist-Ausnahme.

### S07 - nr-vault DefaultDnsResolver

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Classes/Http/DefaultDnsResolver.php

Vollständig gelesen; dns_get_record A/AAAA und Leerantwortsemantik.

### S08 - nr-vault ADR-039: Streaming ohne Verlust des DNS-Pins

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Documentation/Developer/Adr/ADR-039-StreamingSendKeepsTheDnsPin.rst

Ausführliche Abschnitte zu Entscheidung, curl-multi, Headers, Puffern, Fehlern und Zeitgrenzen gelesen; vorhandene Messungen nicht erneut durchgeführt.

### S09 - libcurl: CURLOPT_RESOLVE

Quelle: https://curl.se/libcurl/c/CURLOPT_RESOLVE.html

Offizielle Referenz zum Host-Port-Pin und Multi-Address-Format; funktionale Untergrenzen sind keine Securitybaseline.

### S10 - IANA IPv4 Special-Purpose Address Space

Quelle: https://www.iana.org/assignments/iana-ipv4-special-registry/iana-ipv4-special-registry.xhtml

Register und Bedeutung Globally Reachable gelesen. Angezeigter Registerstand 2025-10-09; bei Implementierung erneut versionieren.

### S11 - IANA IPv6 Special-Purpose Address Space

Quelle: https://www.iana.org/assignments/iana-ipv6-special-registry/iana-ipv6-special-registry.xhtml

Register inklusive IPv4-mapped, NAT64, Documentation und ULA gelesen. Angezeigter Stand 2025-10-09.

### S12 - Guzzle: Connection reuse and sharing

Quelle: https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/docs/contributing/curl-connection-reuse.md

Insbesondere Pooling, raw-option gating, Sharing und versionsabhängige Grenzen gelesen. Entwicklungsbranchstand, keine pauschale Aussage über alle Releases.

### S13 - TYPO3 Core composer.json

Quelle: https://github.com/TYPO3/typo3/blob/cfa1b1cbf3022c19ba231731da397f5847f3c29a/typo3/sysext/core/composer.json

require-Abschnitt gelesen; PHP ^8.2, Guzzle ^7.15.2 oder ^8.0 im untersuchten 14.3-Stand.

### S14 - Guzzle CurlMultiHandler

Quelle: https://github.com/guzzle/guzzle/blob/93939470950a9b11e2e84204166ef5e048c55fe4/src/Handler/CurlMultiHandler.php

Gezielter Anfangsausschnitt mit Handler-/Sharing-/Pool-Lebensdauer gelesen; nicht alle Interna dieses großen Handlers auditiert.

### S15 - Guzzle request options

Quelle: https://docs.guzzlephp.org/en/stable/request-options.html

Primärreferenz für Optionen und on_stats; normative neue Optionen dieses Entwurfs sind davon getrennt.

### S16 - nr-vault composer.json

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/composer.json

Abhängigkeiten, Lizenz und optionales ext-curl gelesen. extra.version ist kein selbstständiger Beleg für einen Release.

### S17 - libcurl-Maintainer zur Zielauflösung hinter HTTP-Proxies

Quelle: https://curl.se/mail/lib-2018-03/0024.html

Primärquelle des Maintainers: Originauflösung liegt beim HTTP-Proxy; als technische Begründung, nicht als aktueller Vulnerabilitynachweis verwendet.

### S18 - OWASP SSRF Prevention Cheat Sheet

Quelle: https://cheatsheetseries.owasp.org/cheatsheets/Server_Side_Request_Forgery_Prevention_Cheat_Sheet.html

Grundlagen zu Allowlist-/Public-Internet-Fällen, DNS und Netzwerkebene. Produktentscheidungen dieses Entwurfs sind eigene Ableitungen.

### S19 - nr-vault ADR-026: DNS rebinding defence

Quelle: https://github.com/netresearch/t3x-nr-vault/blob/5a070c396a614e5b05f63d79fa564c3748cf21eb/Documentation/Developer/Adr/ADR-026-DnsRebindingDefence.rst

Vollständig gelesen; einschließlich ausdrücklicher Änderung durch ADR-038.

### S20 - Guzzle handlers and middleware

Quelle: https://docs.guzzlephp.org/en/stable/handlers-and-middleware.html

Primärreferenz für Handler-/Promise-Vertrag und Middlewarekomposition.

### S21 - AWS: Supporting Amazon VPC services

Quelle: https://docs.aws.amazon.com/whitepapers/latest/ipv6-on-aws/supporting-amazon-vpc-services.html

Offizielle AWS-Referenz zu IMDS an 169.254.169.254 und fd00:ec2::254; abgerufen 2026-10-08.

### S22 - Alibaba Cloud ECS: Instance metadata

Quelle: https://www.alibabacloud.com/help/en/ecs/user-guide/view-instance-metadata/

Offizielle Referenz zu 100.100.100.200; angezeigter Dokumentstand 2026-07-17, abgerufen 2026-10-08.

### S23 - Microsoft Azure: IP address 168.63.129.16

Quelle: https://learn.microsoft.com/en-us/azure/virtual-network/what-is-ip-address-168-63-129-16

Offizielle Referenz zu virtueller Plattform-/WireServeradresse; Anwendungsclientregel ausdrücklich von benötigtem VM-Plattformverkehr unterschieden.
