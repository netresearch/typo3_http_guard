// SPDX-License-Identifier: MIT
// SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
export const DE = {
 'Loopback CIDRs need allowLoopback and an exact /32 or /128, even when another address is requested.':'Loopback-CIDRs benötigen allowLoopback und eine exakte /32- oder /128-Freigabe, auch wenn eine andere Adresse angefragt wird.',
 'Unused loopback CIDR invalidates profile':'Ungenutzter Loopback-CIDR macht das Profil ungültig','Mapped endpoint CIDR rejected':'Eingebetteter Endpunkt-CIDR abgelehnt',
 'CIDR host bits rejected':'CIDR mit gesetzten Host-Bits abgelehnt','Noncanonical CIDR prefix rejected':'Nicht kanonisches CIDR-Präfix abgelehnt','Noncanonical IPv6 CIDR rejected':'Nicht kanonischer IPv6-CIDR abgelehnt',
 'Loopback':'Loopback',
 'TEST-NET-1':'TEST-NET-1','TEST-NET-2':'TEST-NET-2','TEST-NET-3':'TEST-NET-3',
 'Unallocated gap inside 2000::/3':'Nicht zugeteilte Lücke innerhalb 2000::/3','Repository':'Repository','Repository corpus':'Repository-Korpus','✓ Passed':'✓ Bestanden','Invalid scenario JSON.':'Ungültiges Szenario-JSON.',
 'nr-http-guard · Policy explorer':'nr-http-guard · Regeln erkunden',
 'Skip to content':'Zum Inhalt springen','Policy explorer':'Regeln erkunden','POLICY LAB':'REGEL-LABOR','Browser simulation':'Browser-Simulation',
 'TYPO3 · OUTBOUND REQUEST SECURITY':'TYPO3 · AUSGEHENDE HTTP-ANFRAGEN ABSICHERN',
 'See what gets through.':'Was darf nach draußen?',
 'Explore a target. Change a policy. Understand the decision.':'Ziel eingeben. Regeln anpassen. Entscheidung nachvollziehen.',
 'Scoped exceptions':'Gezielte Ausnahmen','Deterministic simulation':'Nachvollziehbare Simulation',
 'REQUEST → POLICY → DECISION':'ANFRAGE → REGELN → ENTSCHEIDUNG',
 'Explorer':'Explorer','Address atlas':'Adressatlas','Validation':'Prüfungen','Policy model':'Regelmodell',
 'REQUEST LAB':'ANFRAGEN PRÜFEN','Test an outbound target':'Ein ausgehendes Ziel prüfen',
 'Runs in your browser · no target requests':'Läuft im Browser · keine Anfragen an die Ziele',
 'Request target':'Anfrageziel','URL, host or IP address':'URL, Host oder IP-Adresse',
 'Bare hosts and IPs assume HTTPS. IPv6 in a URL needs [brackets].':'Für einzelne Hosts und IPs wird HTTPS angenommen. IPv6-Adressen in URLs benötigen [Klammern].',
 'Simulated DNS answers':'Simulierte DNS-Antworten',
 'Supply all representative answers, one IP per line. No DNS lookup is performed.':'Alle Beispielantworten angeben, eine IP pro Zeile. Es findet keine DNS-Auflösung statt.',
 'IPv6 literal · DNS not needed':'IPv6-Adresse · kein DNS nötig',
 'Evaluate request':'Anfrage prüfen','Policy configuration':'Regeln konfigurieren','Reset policy':'Regeln zurücksetzen',
 'Client context':'Client-Kontext','Public client':'Öffentlicher Client','Bound endpoint':'Gebundener Endpunkt',
 'Public unicast only. Configuring an endpoint does not grant access to ordinary URL importers.':'Nur öffentliche Unicast-Adressen. Eine Endpunkt-Konfiguration erteilt gewöhnlichen URL-Importern keine Freigabe.',
 'Simulates a trusted client bound to this endpoint. The grant never applies to other clients.':'Simuliert einen vertrauenswürdigen Client, der an diesen Endpunkt gebunden ist. Die Freigabe gilt für keinen anderen Client.',
 'Simulated bound endpoint grant':'Simulierte Freigabe für einen gebundenen Endpunkt','Scoped allow rule':'Gezielte Freigaberegel',
 'Trusted client binding · simulated':'Vertrauenswürdige Client-Bindung · simuliert',
 'Exact endpoint origin':'Exakte Origin des Endpunkts','Allowed IPs / CIDRs':'Erlaubte IPs / CIDRs','Allowed methods':'Erlaubte Methoden',
 'Comma-separated HTTP methods':'HTTP-Methoden, durch Kommas getrennt',
 'IPv4: /24 or narrower. IPv6: /64 or narrower. No hostname wildcard or general “allow private” switch.':'IPv4: /24 oder enger. IPv6: /64 oder enger. Keine Host-Wildcards und keine pauschale Freigabe privater Netze.',
 'Explicitly permit loopback with an exact /32 or /128':'Loopback ausdrücklich mit einer exakten /32- oder /128-Freigabe erlauben',
 'Operator deny rules':'Betreiberweite Sperrregeln','Always take precedence':'Haben immer Vorrang',
 'One IP or CIDR per line. These apply to every client, including allowed endpoints.':'Eine IP oder ein CIDR pro Zeile. Die Regeln gelten für alle Clients, auch für freigegebene Endpunkte.',
 'Additional Core host allowlist':'Zusätzliche Host-Allowlist des Cores','Optional':'Optional','Exact Core hosts':'Exakte Hosts der Core-Allowlist',
 'Exact hosts only. Empty adds no restriction. This intersects the guard policy; it never grants private access.':'Nur exakte Hosts. Leer bedeutet keine zusätzliche Einschränkung. Die Liste schränkt die Guard-Regeln weiter ein; sie erteilt keine Freigabe für private Ziele.',
 'Export scenario ↓':'Szenario exportieren ↓','Import scenario':'Szenario importieren',
 'POLICY DECISION':'REGELENTSCHEIDUNG','Simulation':'Simulation','All candidates must pass':'Alle Adressen müssen zulässig sein','Copy result':'Ergebnis kopieren',
 'Address classification':'Adressklassifikation','Decision trace':'Entscheidungsweg','In evaluation order':'In Prüfreihenfolge',
 'A policy preview is not a connection authorization. The extension must recheck every send, retry and redirect and pin the validated addresses.':'Die Vorschau erteilt keine Verbindungsfreigabe. Die Extension muss vor jedem Versand, Retry und Redirect erneut prüfen und die Verbindung an die geprüften Adressen binden.',
 'ADDRESS INTELLIGENCE':'ADRESSBEREICHE','Know the destination':'Das Ziel einordnen',
 'A conservative public profile, with explicit exceptions for selected private endpoints.':'Ein restriktives Profil für öffentliche Ziele, mit gezielten Ausnahmen für ausgewählte private Endpunkte.',
 'Find a range or classification':'Bereich oder Klassifikation suchen','Address family':'Adressfamilie','Address range':'Adressbereich','Classification':'Klassifikation','Endpoint exception':'Endpunkt-Ausnahme',
 'No matching ranges.':'Keine passenden Bereiche.',
 'Public IPv6 is deliberate':'Öffentliches IPv6 braucht eine Zuteilung',
 'Public IPv6 must match an allocated global-unicast prefix from the bundled corpus and lie outside special-purpose ranges. Membership in':'Öffentliches IPv6 muss zu einem zugeteilten Global-Unicast-Präfix aus dem Korpus gehören und außerhalb der Spezialbereiche liegen. Die Zugehörigkeit zu',
 'alone does not grant access.':'allein erteilt keine Freigabe.',
 'Mapped addresses keep IPv4 rules':'Für eingebettetes IPv4 gelten IPv4-Regeln',
 'is classified by the embedded IPv4 address. An IPv6 representation cannot bypass IPv4 deny rules.':'wird anhand der eingebetteten IPv4-Adresse klassifiziert. Die IPv6-Darstellung umgeht keine IPv4-Sperrregel.',
 'Special purpose stays special':'Spezialbereiche bleiben gesperrt',
 'The public profile blocks special-purpose allocations even where IANA marks them globally reachable. “Routable” does not make an address an appropriate HTTP target.':'Das öffentliche Profil sperrt Spezialbereiche auch dann, wenn IANA sie als global erreichbar einstuft. Routbarkeit allein macht eine Adresse nicht zu einem geeigneten HTTP-Ziel.',
 'REPRESENTATIVE TEST CORPUS':'REPRÄSENTATIVER TESTKORPUS','Put the model to the test':'Das Modell prüfen',
 'Expected allow/block outcomes and reason codes, executed against the same engine as the explorer.':'Erwartete Freigaben, Sperren und Regelcodes, geprüft mit derselben Engine wie im Explorer.',
 'Run validation ↻':'Prüfungen ausführen ↻','Filter results':'Ergebnisse filtern','All cases':'Alle Fälle','Rule composition':'Zusammenspiel der Regeln','DNS answer sets':'DNS-Antwortmengen','URL normalization':'URL-Normalisierung','Failed cases':'Fehlgeschlagene Fälle',
 '“Inspect” loads that case and its policy into the explorer.':'„Ansehen“ lädt den Testfall mit seinen Regeln in den Explorer.',
 'Test case':'Testfall','Expected':'Erwartet','Observed reason':'Ermittelter Regelcode','Result':'Ergebnis','Action':'Aktion','No cases match this filter.':'Keine Fälle passen zu diesem Filter.',
 'These are prototype policy checks. They do not verify PHP, TYPO3 integration, cURL, live DNS, redirects, cache isolation or wire behavior.':'Diese Prüfungen testen das Browser-Regelmodell. Sie prüfen weder PHP und die TYPO3-Integration noch cURL, echte DNS-Auflösung, Redirects, Cache-Isolation oder Netzwerkverkehr.',
 'DESIGN BOUNDARIES':'GELTUNGSBEREICH','One extension. Explicit trust.':'Eine Extension. Gezielte Freigaben.',
 'Browser policy simulation for netresearch/typo3_http_guard. Address rules come from the repository’s versioned corpus.':'Browser-Simulation für netresearch/typo3_http_guard. Die Adressregeln stammen aus dem versionierten Korpus des Repositories.',
 'Policy simulation':'Regelsimulation','Normalize before deciding':'Vor der Entscheidung normalisieren',
 'HTTP(S), an exact authority and a valid port. Reject userinfo, fragments, zone IDs, Unicode hosts, numeric shortcuts and ambiguous syntax. A single final DNS dot is normalized.':'HTTP(S), eine eindeutige Authority und ein gültiger Port sind Pflicht. Zugangsdaten in der URL, Fragmente, Zone-IDs, Unicode-Hosts, verkürzte IP-Schreibweisen und mehrdeutige Syntax werden abgelehnt. Ein einzelner abschließender DNS-Punkt wird entfernt.',
 'Deny rules have priority':'Sperrregeln haben Vorrang',
 'Operator denies and immutable special-purpose blocks cannot be overridden. The public client only permits ordinary public unicast. No first-match-wins behavior.':'Betreiberweite Sperren und feste Verbote für Spezialbereiche lassen sich nicht überschreiben. Der öffentliche Client erlaubt nur reguläres öffentliches Unicast. Die erste passende Regel entscheidet nicht automatisch.',
 'Grant a client, not a URL':'Die Freigabe an einen Client binden',
 'An internal exception binds a trusted client to an exact scheme, host, port, method set and narrow IP set. The endpoint selector here simulates that trusted binding; a user-selectable string must never grant it in production.':'Eine interne Ausnahme bindet einen vertrauenswürdigen Client an Schema, Host, Port, Methoden und eng begrenzte IP-Bereiche. Die Endpunktauswahl simuliert hier diese Bindung. Im Produkt darf ein frei wählbarer String keine solche Freigabe erteilen.',
 'Check the whole answer set':'Die vollständige Antwortmenge prüfen',
 'Every usable A and AAAA answer must pass. Missing, invalid or excessive answers fail closed. A grant cannot turn a hostname into a trusted destination.':'Jede verwendbare A- und AAAA-Antwort muss zulässig sein. Fehlende, ungültige oder zu viele Antworten führen zur Ablehnung. Eine Freigabe macht einen Hostnamen nicht pauschal vertrauenswürdig.',
 'Transport completes the protection':'Der Transport vervollständigt den Schutz',
 'The extension requires controlled cURL, DNS pinning, isolated transfers and fresh checks for redirects and retries. Those transport guarantees are outside this browser simulation.':'Die Extension benötigt kontrolliertes cURL, DNS-Pinning, isolierte Transfers und erneute Prüfungen für Redirects und Retries. Diese Transportgarantien bildet die Browser-Simulation nicht ab.',
 'Keep the scope intact':'Den Umfang einhalten','The product is':'Das Produkt ist',
 ', an independent TYPO3 extension.':', eine eigenständige TYPO3-Extension.',
 'nr-vault and nr-llm are references only.':'nr-vault und nr-llm dienen ausschließlich als Referenzen.',
 'Adapters, migrations and changes to either project are out of scope.':'Adapter, Migrationen und Änderungen an diesen Projekten sind nicht Teil des Auftrags.',
 'Model notes & sources':'Hinweise zum Modell und Quellen',
 'Address classifications are generated from Resources/Private/HttpGuard/data/security-corpus/address-rules.json in this repository, including allocated IPv6 prefixes. Rule IDs in this explorer describe the simulation; they are not the PHP exception reason codes.':'Die Adressklassifikation wird aus Resources/Private/HttpGuard/data/security-corpus/address-rules.json dieses Repositories erzeugt, einschließlich zugeteilter IPv6-Präfixe. Die Regelcodes im Explorer beschreiben die Simulation; sie entsprechen nicht den Fehlercodes der PHP-Exceptions.',
 'Project documentation ↗':'Projektdokumentation ↗','IANA IPv4 special-purpose registry ↗':'IANA-Register für IPv4-Spezialbereiche ↗','IANA IPv6 special-purpose registry ↗':'IANA-Register für IPv6-Spezialbereiche ↗','OWASP SSRF prevention ↗':'OWASP: SSRF verhindern ↗',
 'Prototype simplifications: one simulated endpoint, optional exact-host Core allowlist, no profile expiry or ownership workflow, no redirects or transport options. CONNECT and TRACE are rejected. Unknown environment-specific routing and custom NAT64 prefixes require operator controls. All inputs remain in memory; exporting or copying is explicit. No analytics, target fetches or DNS requests.':'Vereinfachungen: ein simulierter Endpunkt, optionale Core-Allowlist mit exakten Hosts, kein Profilablauf und keine Zuständigkeitsverwaltung, keine Redirects oder Transportoptionen. CONNECT und TRACE werden abgelehnt. Eigenes Routing und eigene NAT64-Präfixe erfordern zusätzliche Betreiberregeln. Alle Eingaben bleiben im Arbeitsspeicher; Export und Kopieren erfolgen ausdrücklich. Keine Analyse-Dienste, Zielabfragen oder DNS-Anfragen.',
 'Engineering secure connections.':'Sichere Verbindungen entwickeln.','nr-http-guard · Interactive planning prototype':'nr-http-guard · Interaktiver Regel-Explorer',
 'Enable JavaScript to use this local policy simulation.':'JavaScript aktivieren, um die lokale Regelsimulation zu verwenden.',
 'Explore SSRF address classifications and scoped outbound policies for the Netresearch TYPO3 extension.':'SSRF-Adressklassen und gezielte Freigaberegeln für ausgehende Anfragen der Netresearch-TYPO3-Extension erkunden.',
 'Netresearch website':'Netresearch-Website','Switch color theme':'Farbschema wechseln','Switch to dark theme':'Dunkles Farbschema aktivieren','Switch to light theme':'Helles Farbschema aktivieren',
 'Explorer views':'Ansichten des Explorers','Representative scenarios':'Beispielszenarien','HTTP method':'HTTP-Methode',
 'e.g. 8.8.8.0/24 or 2001:4860::/32':'z. B. 8.8.8.0/24 oder 2001:4860::/32','Try loopback, NAT64, 192.168…':'Zum Beispiel Loopback, NAT64, 192.168…','Language':'Sprache',
 'Public IPv6':'Öffentliches IPv6','Global unicast':'Globales Unicast','Local machine':'Lokaler Rechner','Internal ERP':'Internes ERP','Scoped exception':'Gezielte Ausnahme','Cloud metadata':'Cloud-Metadaten','Immutable deny':'Festes Verbot','Mixed DNS':'Gemischtes DNS','One bad candidate':'Eine unzulässige Adresse','Mapped IPv4':'Eingebettetes IPv4','Same IPv4 policy':'Gleiche IPv4-Regeln',
 'Public IPv4':'Öffentliches IPv4','Unspecified / this network':'Unbestimmt / dieses Netz','Private IPv4':'Privates IPv4','Shared address space':'Gemeinsamer Adressraum','Link-local':'Link-Local','Special purpose':'Spezialbereich','Documentation':'Dokumentation','Benchmarking':'Benchmarking','Reserved / broadcast':'Reserviert / Broadcast','Transition / translation':'Übergangs- / Übersetzungsverfahren','Discard-only':'Discard-Bereich','Unique local IPv6':'Private IPv6-Adresse (ULA)','Cloud platform':'Cloud-Plattform','IPv4-mapped IPv6':'IPv4-eingebettetes IPv6','Unallocated / non-global IPv6':'Nicht zugeteiltes / nicht globales IPv6','Global unicast IPv6':'Globales IPv6-Unicast',
 'outside allocated global prefixes':'außerhalb zugeteilter globaler Präfixe','outside special-purpose ranges':'außerhalb der Spezialbereiche','Repository corpus':'Repository-Korpus','Public profile':'Öffentliches Profil','IANA global unicast allocation':'IANA-Global-Unicast-Zuteilung',
 'Request allowed':'Anfrage erlaubt','Request blocked':'Anfrage gesperrt','ALLOW':'ERLAUBT','BLOCK':'GESPERRT','PUBLIC CLIENT':'ÖFFENTLICHER CLIENT','BOUND ENDPOINT':'GEBUNDENER ENDPUNKT',
 'Rule':'Regel','Origin':'Origin','Method':'Methode','✓ Pass':'✓ Zulässig','× Block':'× Gesperrt','Allow':'Erlauben','Block':'Sperren','× Blocked':'× Gesperrt','× Fail':'× Fehlgeschlagen',
 'Scoped grant only':'Nur mit gezielter Freigabe','Exact IP + explicit opt-in':'Exakte IP + ausdrückliche Freigabe','No override':'Keine Ausnahme','Normalize embedded IPv4':'Eingebettetes IPv4 normalisieren','Classify IPv4':'IPv4 klassifizieren',
 'No address evaluated. Resolve the target, configuration or DNS issue shown above.':'Keine Adresse geprüft. Zuerst das oben angezeigte Problem mit Ziel, Konfiguration oder DNS beheben.',
 'Representative cases':'Beispiel-Testfälle','Passed expected checks':'Erwartungen erfüllt','Failed checks':'Fehlgeschlagene Prüfungen','Inspect →':'Ansehen →','Addresses':'Adressen','Policy':'Regeln','DNS':'DNS','Normalization':'Normalisierung',
 'Policy reset to the public profile':'Regeln auf das öffentliche Profil zurückgesetzt','Decision JSON copied':'Entscheidung als JSON kopiert','Clipboard unavailable. Use Export scenario to save the inputs.':'Zwischenablage nicht verfügbar. Die Eingaben lassen sich als Szenario exportieren.','Scenario exported':'Szenario exportiert','Scenario imported':'Szenario importiert','Scenario imported.':'Szenario importiert.',
 'Scenario file exceeds 100 KB.':'Die Szenario-Datei ist größer als 100 KB.','Choose an exported nr-http-guard scenario.':'Ein exportiertes nr-http-guard-Szenario auswählen.','Unsupported HTTP method.':'Nicht unterstützte HTTP-Methode.',
 'Invalid target':'Ungültiges Ziel','Enter a target of 1–4096 characters.':'Ein Ziel mit 1–4096 Zeichen eingeben.','Whitespace, controls and backslashes are forbidden.':'Leerzeichen, Steuerzeichen und Backslashes sind verboten.','URL fragments are forbidden.':'URL-Fragmente sind verboten.','Use an absolute HTTP(S) URL, host, or IP address.':'Eine absolute HTTP(S)-URL, einen Host oder eine IP-Adresse verwenden.','Only HTTP and HTTPS are permitted.':'Nur HTTP und HTTPS sind erlaubt.','URL userinfo is forbidden.':'Zugangsdaten in der URL sind verboten.','Host percent-encoding, zone IDs and Unicode hosts are forbidden; use an ASCII A-label.':'Prozentkodierung im Host, Zone-IDs und Unicode-Hosts sind verboten. Ein ASCII-A-Label verwenden.','Invalid bracketed IPv6 authority.':'Ungültige IPv6-Authority in Klammern.','Invalid IPv6 literal.':'Ungültige IPv6-Adresse.','IPv6 literals in URLs require square brackets.':'IPv6-Adressen in URLs benötigen eckige Klammern.','Ambiguous numeric host: use canonical dotted-decimal IPv4.':'Mehrdeutiger numerischer Host: IPv4 mit vier Dezimalzahlen und Punkten verwenden.','Invalid ASCII hostname.':'Ungültiger ASCII-Hostname.','Port must be between 1 and 65535.':'Der Port muss zwischen 1 und 65535 liegen.',
 'Method blocked':'Methode gesperrt','CONNECT and TRACE are not supported by this explorer.':'Dieser Explorer unterstützt CONNECT und TRACE nicht.','Invalid policy configuration':'Ungültige Regelkonfiguration','Use at most 128 operator deny rules.':'Höchstens 128 betreiberweite Sperrregeln verwenden.','Endpoint origin must be absolute, without path or query.':'Die Origin des Endpunkts muss absolut sein und darf weder Pfad noch Query enthalten.','Provide 1–128 endpoint CIDRs.':'1–128 Endpunkt-CIDRs angeben.','Endpoint CIDRs must be /24 or narrower for IPv4 and /64 or narrower for IPv6.':'Endpunkt-CIDRs müssen für IPv4 /24 oder enger und für IPv6 /64 oder enger sein.','Provide valid endpoint methods; CONNECT is forbidden.':'Gültige Endpunkt-Methoden angeben; CONNECT ist verboten.','Core allowlist accepts exact hosts only, without wildcards, ports or paths.':'Die Core-Allowlist akzeptiert nur exakte Hosts ohne Wildcards, Ports oder Pfade.','Core allowlist accepts hosts, not ports.':'Die Core-Allowlist akzeptiert Hosts, keine Ports.',
 'Normalize target':'Ziel normalisieren','Validate configuration':'Konfiguration prüfen','Core host allowlist rejected':'Core-Host-Allowlist lehnt das Ziel ab','The exact target host is absent from the additional Core allowlist.':'Der exakte Zielhost fehlt in der zusätzlichen Core-Allowlist.','Endpoint scope mismatch':'Ziel passt nicht zum gebundenen Endpunkt','No simulated DNS answers':'Keine simulierten DNS-Antworten','Enter every representative A and AAAA candidate. This tool does not resolve hostnames.':'Alle A- und AAAA-Beispieladressen angeben. Dieses Werkzeug löst keine Hostnamen auf.','Too many DNS candidates':'Zu viele DNS-Adressen','The draft limits an answer set to 64 usable addresses.':'Eine Antwortmenge ist auf 64 verwendbare Adressen begrenzt.','Invalid DNS candidate':'Ungültige DNS-Adresse','Each simulated answer must be a canonical IPv4 or valid IPv6 literal, without brackets.':'Jede simulierte Antwort muss eine kanonische IPv4- oder gültige IPv6-Adresse ohne Klammern sein.','Use address literal':'IP-Adresse direkt verwenden','Read simulated DNS answers':'Simulierte DNS-Antworten lesen','Apply all address policies':'Alle Adressregeln anwenden',
 'Address is outside the bound endpoint’s IP set.':'Die Adresse liegt außerhalb der IP-Bereiche des gebundenen Endpunkts.','Loopback needs allowLoopback and an exact /32 or /128 grant.':'Loopback benötigt allowLoopback und eine exakte /32- oder /128-Freigabe.','Regular public unicast outside excluded special-purpose ranges.':'Reguläres öffentliches Unicast außerhalb gesperrter Spezialbereiche.','The request matches the bound endpoint’s origin, method and address set.':'Die Anfrage passt zu Origin, Methode und IP-Bereichen des gebundenen Endpunkts.','Every candidate is regular public unicast outside excluded special-purpose ranges.':'Alle Adressen sind reguläres öffentliches Unicast außerhalb gesperrter Spezialbereiche.',
 'public client':'öffentlicher Client','bound endpoint':'gebundener Endpunkt',
 'Unspecified IPv4':'Unbestimmtes IPv4','This network':'Dieses Netz','RFC1918 /8':'RFC1918 /8','RFC1918 /12 start':'RFC1918 /12 Anfang','RFC1918 /12 end':'RFC1918 /12 Ende','Outside RFC1918 /12':'Außerhalb RFC1918 /12','RFC1918 /16':'RFC1918 /16','CGNAT start':'CGNAT Anfang','CGNAT end':'CGNAT Ende','Outside CGNAT':'Außerhalb CGNAT','IPv4 loopback':'IPv4-Loopback','Entire loopback /8':'Gesamter Loopback-Bereich /8','IPv4 link-local':'IPv4-Link-Local','AWS metadata IPv4':'AWS-Metadaten IPv4','AWS metadata IPv6':'AWS-Metadaten IPv6','Alibaba metadata':'Alibaba-Metadaten','Azure platform':'Azure-Plattform','Protocol assignments':'Protokollzuweisungen','Benchmark IPv4':'IPv4-Benchmarking','Multicast IPv4':'IPv4-Multicast','Reserved IPv4':'Reserviertes IPv4','Limited broadcast':'Begrenzter Broadcast','IPv6 unspecified':'Unbestimmtes IPv6','IPv6 loopback':'IPv6-Loopback','ULA fc':'ULA fc','ULA fd':'ULA fd','IPv6 link-local start':'IPv6-Link-Local Anfang','IPv6 link-local end':'IPv6-Link-Local Ende','IPv6 multicast':'IPv6-Multicast','IPv6 documentation':'IPv6-Dokumentation','IPv6 documentation 3fff':'IPv6-Dokumentation 3fff','IPv6 benchmark':'IPv6-Benchmarking','NAT64 WKP':'NAT64 WKP','NAT64 local':'Lokales NAT64','6to4':'6to4','Teredo':'Teredo','Deprecated IPv4-compatible':'Veraltetes IPv4-kompatibles IPv6','Discard-only':'Discard-Bereich','Dummy IPv6':'IPv6-Dummy-Präfix','Unallocated IPv6':'Nicht zugeteiltes IPv6','Mapped private':'Eingebettetes privates IPv4','Mapped public':'Eingebettetes öffentliches IPv4','Mapped hex loopback':'Eingebettetes Loopback in Hexadezimaldarstellung','Mapped metadata':'Eingebettete Metadaten-Adresse',
 'Scoped private endpoint':'Gezielt freigegebener privater Endpunkt','No grant despite static answer':'Keine Freigabe trotz statischer Antwort','Operator deny wins':'Betreiberweite Sperre hat Vorrang','Wrong origin port':'Abweichender Origin-Port','Wrong origin scheme':'Abweichendes Origin-Schema','Wrong method':'Abweichende Methode','Address outside endpoint set':'Adresse außerhalb der Endpunkt-IP-Bereiche','Endpoint does not grant arbitrary public targets':'Endpunkt erteilt keine Freigabe für beliebige öffentliche Ziele','No broad private grants':'Keine pauschalen privaten Freigaben','Mapped broad grant rejected':'Pauschale Freigabe eingebetteter Adressen abgelehnt','Narrow IPv6 endpoint':'Eng begrenzter IPv6-Endpunkt','Loopback requires opt-in':'Loopback erfordert ausdrückliche Freigabe','Loopback explicit opt-in':'Loopback ausdrücklich freigegeben','Loopback requires exact host grant':'Loopback erfordert exakte Host-Freigabe','Broad loopback CIDR invalidates exact grant':'Zu breiter Loopback-CIDR macht die exakte Freigabe ungültig','IPv6 loopback opt-in':'IPv6-Loopback ausdrücklich freigegeben','Metadata grant cannot override block':'Metadaten-Freigabe kann Sperre nicht überschreiben','Public operator deny':'Betreiberweite Sperre für öffentliche Adresse','Mapped operator deny':'Betreiberweite Sperre für eingebettete Adresse','Core allowlist intersects':'Core-Allowlist schränkt weiter ein','Core allowlist does not grant private':'Core-Allowlist erteilt keine private Freigabe','All DNS candidates pass':'Alle DNS-Adressen sind zulässig','Mixed public/private DNS':'Öffentliche und private DNS-Antworten','Mixed public/loopback IPv6 DNS':'Öffentliche und IPv6-Loopback-DNS-Antworten','Empty DNS fails closed':'Leere DNS-Antwort führt zur Ablehnung','Invalid DNS fails closed':'Ungültige DNS-Antwort führt zur Ablehnung','Too many DNS candidates':'Zu viele DNS-Adressen','Integer IP':'IP als Ganzzahl','Hex IP':'IP als Hexadezimalzahl','Octal IPv4':'Oktales IPv4','Short IPv4':'Verkürztes IPv4','Userinfo':'Zugangsdaten in der URL','Fragment':'Fragment','Unicode host':'Unicode-Host','Zone ID':'Zone-ID','Unsupported scheme':'Nicht unterstütztes Schema','Backslash':'Backslash','Bad port':'Ungültiger Port','Repeated FQDN dot':'Mehrfacher abschließender FQDN-Punkt','Empty port':'Leerer Port','Whitespace':'Leerzeichen','Malformed IPv6':'Fehlerhafte IPv6-Adresse','Canonical hostname and FQDN dot':'Kanonischer Hostname und abschließender FQDN-Punkt','Invalid deny fails closed':'Ungültige Sperrregel führt zur Ablehnung','CONNECT forbidden':'CONNECT ist verboten',
};
const patterns = [
 [/^Use an IPv4 CIDR instead of an IPv4-mapped IPv6 CIDR: (.+)$/,(_,c)=>'Für eingebettetes IPv4 einen IPv4-CIDR verwenden: '+c],
 [/^CIDR must use the network address: (.+)$/,(_,c)=>'CIDR muss die Netzadresse verwenden: '+c],
 [/^CIDR address must use canonical spelling: (.+)$/,(_,c)=>'CIDR-Adresse muss kanonisch geschrieben sein: '+c],
 [/^· (.+)$/,(_,s)=>'· '+translate(s,'de')],
 [/^IPv([46]) literal · DNS not needed$/,(_,v)=>`IPv${v}-Adresse · kein DNS nötig`],
 [/^Hostname · simulated DNS required$/,()=> 'Hostname · simulierte DNS-Antworten erforderlich'],
 [/^Target needs attention$/,()=> 'Ziel prüfen'],
 [/^(ALLOW|BLOCK) · (PUBLIC CLIENT|BOUND ENDPOINT)$/,(_,a,b)=>DE[a]+' · '+DE[b]],
 [/^(\d+) candidates?$/,(_,n)=>`${n} ${n==='1'?'Adresse':'Adressen'}`],
 [/^IPv4-mapped IPv6 →$/,()=> 'Eingebettetes IPv4 →'],
 [/^IPv4-mapped IPv6 → (.+)$/,(_,ip)=>'Eingebettetes IPv4 → '+ip],
 [/^Invalid (CIDR|IP or CIDR|prefix length): (.+)$/,(_,kind,value)=>({CIDR:'Ungültiger CIDR','IP or CIDR':'Ungültige IP oder CIDR','prefix length':'Ungültige Präfixlänge'}[kind])+': '+value],
 [/^Invalid scenario (field|flag): (.+)$/,(_,kind,key)=>`Ungültiges Szenario-${kind==='field'?'Feld':'Flag'}: ${key}`],
 [/^Scenario field too long: (.+)$/,(_,key)=>'Szenario-Feld zu lang: '+key],
 [/^(https?:\/\/\S+) · HTTPS assumed for a representative host\/IP$/,(_,o)=>o+' · HTTPS für den eingegebenen Host / die IP angenommen'],
 [/^(\d+) operator deny rule\(s\) · (public client|bound endpoint)$/,(_,n,kind)=>`${n} betreiberweite Sperrregel(n) · ${DE[kind]}`],
 [/^The bound client permits only (.+) to (.+)\.$/,(_,m,o)=>`Der gebundene Client erlaubt nur ${m} an ${o}.`],
 [/^(\d+) candidate\(s\) · (DNS not needed|manual fixture; no lookup performed)$/,(_,n,d)=>`${n} Adresse(n) · ${d==='DNS not needed'?'kein DNS nötig':'manuelle Beispiele; keine Auflösung'}`],
 [/^Operator deny (.+) takes precedence over all grants\.$/,(_,c)=>`Die betreiberweite Sperre ${c} hat Vorrang vor allen Freigaben.`],
 [/^Immutable special-purpose \/ platform block: (.+)\.$/,(_,c)=>`Festes Verbot für Spezialbereich / Plattformziel: ${c}.`],
 [/^Exact origin and method match; IP matches (.+)\.$/,(_,c)=>`Origin und Methode stimmen exakt überein; die IP passt zu ${c}.`],
 [/^(.+) requires an explicitly bound, scoped endpoint grant\.$/,(_,label)=>`${DE[label]||label}: Eine gezielte Freigabe für einen gebundenen Endpunkt ist erforderlich.`],
 [/^(.+) One rejected candidate blocks the entire answer set\.$/,(_,s)=>translate(s,'de')+' Eine unzulässige Adresse sperrt die gesamte Antwortmenge.'],
 [/^Every candidate passed\. (.+) applies\.$/,(_,p)=>`Alle Adressen sind zulässig. ${p.startsWith('Endpoint ')?'Endpunkt '+p.slice(9):DE[p]||p} gilt.`],
 [/^Policy evaluated · (.+)$/,(_,code)=>'Regeln geprüft · '+code],
 [/^Validation complete: (\d+) \/ (\d+) passed$/,(_,a,b)=>`Prüfungen abgeschlossen: ${a} / ${b} bestanden`],
 [/^Inspect (.+)$/,(_,c)=>c+' ansehen'],
 [/^Corpus revision: (.+)$/,(_,r)=>'Korpus-Version: '+r],
];
export function translate(text,language='en') {
 if(language!=='de'||typeof text!=='string')return text;
 const core=text.trim();
 if(!core)return text;
 let out=DE[core];
 if(out===undefined){for(const [re,fn] of patterns){const m=core.match(re);if(m){out=fn(...m);break;}}}
 return out===undefined?text:text.slice(0,text.indexOf(core))+out+text.slice(text.indexOf(core)+core.length);
}
// Translate only presentation text and known text attributes. Form values,
// rule codes, URLs and imported scenarios retain their original values.
export function createTranslator(doc,initialLanguage) {
 let language=initialLanguage==='de'?'de':'en';
 const originals=new WeakMap(),attributes=new WeakMap();
 function render(){
  doc.documentElement.lang=language;
  const walker=doc.createTreeWalker(doc.documentElement,4);
  let node;
  while((node=walker.nextNode())){
   if(node.parentElement?.closest('script,style,code'))continue;
   if(!originals.has(node))originals.set(node,node.nodeValue);
   node.nodeValue=translate(originals.get(node),language);
  }
  for(const el of doc.querySelectorAll('[aria-label],[placeholder],[title],meta[name="description"]')){
   if(!attributes.has(el))attributes.set(el,new Map());
   for(const name of ['aria-label','placeholder','title',...(el.matches('meta[name="description"]')?['content']:[])]){
    if(!el.hasAttribute(name))continue;
    const original=attributes.get(el);
    // Theme labels may change after the element was first translated.
    const value=el.getAttribute(name);
    if(!original.has(name)||!Object.values(DE).includes(value)&&value!==translate(original.get(name),language))original.set(name,value);
    el.setAttribute(name,translate(original.get(name),language));
   }
  }
  for(const button of doc.querySelectorAll('[data-language]'))button.setAttribute('aria-pressed',String(button.dataset.language===language));
 }
 return {render,setLanguage(value){language=value==='de'?'de':'en';render();},get language(){return language;}};
}
