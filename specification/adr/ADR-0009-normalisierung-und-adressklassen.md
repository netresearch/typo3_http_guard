# ADR-0009: Ein kanonischer Zielparser und ein versionierter Adresskorpus

**Status:** Vorgeschlagen  
**Datum:** 2026-10-08  
**Entscheidungsträger:** Projektverantwortliche Architektur/Security; Freigabe noch ausstehend  
**Anforderungen:** HG-007, HG-008, HG-009, HG-010, HG-011, HG-012  
**Ablösung:** keine; neue Entscheidung für das vorgeschlagene Produkt

## Kontext

Parser interpretieren nichtkanonische numerische Adressen unterschiedlich. Hostheader und URI können voneinander abweichen. Ein grober Private-IP-Filter deckt nicht alle lokalen oder speziellen Adressklassen ab. IANA führt eigenständige IPv4-/IPv6-Spezialregister. [S04, S05, S10, S11]

## Entscheidung

Der Guard normalisiert einmal, verwendet danach ein typisiertes Target und wendet dieselbe Authority auch beim Transport an. Absolute HTTP-/HTTPS-URLs sind Pflicht; Userinfo, Fragmente, Steuerzeichen, Zone-IDs, Host-Prozentkodierung und nichtkanonische numerische Formen werden abgewiesen. HTTP-Host und URI-Authority müssen nach Normalisierung übereinstimmen. Unicodehostnamen werden in v1 nicht implizit konvertiert; bereits kanonisch umgewandelte ASCII-IDNA-Namen sind zulässig.

IP- und CIDR-Prüfungen erfolgen binär für beide Familien. IPv4-mapped IPv6 wird auf die eingebettete IPv4-Policy zurückgeführt. Tunnel-/Übersetzungsbereiche werden konservativ gesperrt. Der versionierte Korpus benennt Privaträume, Loopback, Link-Local, Metadatenrelevanz, Multicast, Dokumentations-/Benchmark- und weitere Spezialbereiche.

Die v1-Public-Policy ist bewusst konservativer als eine bloße positive Globally-Reachable-Markierung einzelner Spezialadressen. Betreiber-Deny kann zusätzlich auch nominell öffentliche, intern geroutete Bereiche sperren. Registerupdates werden versioniert, nie bei jedem Request live heruntergeladen.

## Verworfene Alternativen

**Nur Regex:** ungeeignet als umfassende IP-/CIDRentscheidung. **Nur PHP-Filterflags:** koppelt Policy unbemerkt an deren konkrete Laufzeitsemantik. **PrivateIPv4 ohne IPv6:** lässt eine zweite Adressfamilie offen. **Jede Parserkorrektur akzeptieren:** vergrößert Interpretationsunterschiede.

## Konsequenzen

Es gibt einen testbaren Entscheidungsweg und nachvollziehbare Datensatzversionen. Einzelne legitime Spezialadressen und Unicodeeingaben sind eingeschränkt; Aufrufer müssen kanonische Ziele liefern. Öffentliche IP bedeutet weiterhin nicht "inhaltlich vertrauenswürdiger Server".

## Nachweis und Abnahme

T008, T009, T010, T011, T012, T013, T014, T015, T016, T017, T018, T019, T020, T021. Details und erwartete Ergebnisse stehen in [06 Tests und Abnahme](../specs/06-verification.md). Diese Tests sind spezifiziert, nicht im Rahmen dieser Dokumentlieferung ausgeführt.

## Anlass für Neubewertung

Eine Lockerung einer gesperrten Adressklasse oder Parserform braucht Korpusänderung, Begründung und Grenztests. Neue IANA-Einträge werden wie sicherheitsrelevante Abhängigkeitsänderungen behandelt.

## Zugehörige Dokumente

[Produktanforderungen](../specs/01-product-requirements.md), [Sicherheitsmodell](../specs/02-security-model.md), [Architektur](../specs/03-architecture.md), [Quellen S01-S23](../specs/08-evidence-and-sources.md), [ADR-Index](README.md).
