# HTTP Guard shared security corpus — preparation revision 2026-10-08

This directory prepares AP-02 data and AP-07 characterization against the original plan. Preparing fixtures does not implement tests, establish G0, or authorize a production release. All original tests remain explicitly `not_run` and requirements remain `not_verified`.

## Files

- `requirements-tests.json` / `.md`: exact 45 HG requirements, exact 84 proposed tests, original text and source line numbers. The minimum-to-test mapping has no missing references. P0=74; P1=10.
- `address-rules.json`: versioned source-derived classification rules, 51 IANA special ranges, three supplemental ranges, four provider hard denies, and allocated/reserved IPv6 global-unicast records.
- `address-cases.json`: 257 binary first/last/adjacent and mapped-address fixtures. Adjacent addresses are evaluated against the entire corpus; an address beside one prohibited range is not assumed public.
- `uri-cases.json`: 33 strict raw-target fixtures. Raw syntax must be preserved before a PSR-7 parser may erase fragments, userinfo delimiters or other ambiguity. Syntax acceptance is not authorization.
- `endpoint-cases.json`: 20 deliberately narrow endpoint and hard-deny composition fixtures. Policy-only allow still needs fresh verified resolution and controlled transport.
- `scenario-cases.json`: 41 declarative DNS, Authority, options, grant, redirect, cache and cancellation scenarios. These are preparation data, not executable test outcomes.
- `sources.json`, `sources/`: primary-source URLs, actual source dates, retrieval date and SHA-256 hashes; downloaded XML, provider pages, RFCs and HTTP headers retained.
- `derived-hashes.json`, `checksums.sha256`: hashes for the original sources and six derived JSON data files. All 14 hashes were verified after preparation.
- `evidence/`: actual legacy Vault baseline output and resolved dependency evidence. Legacy baseline outcomes must never be substituted for a passed HG test.
- `scripts/`: deterministic ledger extraction, structured registry transformation and binary fixture generation. These tools do not send requests or implement the production Guard.

## Policy interpretation

All IANA special-purpose entries are excluded from Public, including entries with `Globally Reachable=True`. The original spec deliberately makes HTTP policy stricter than network routability. The only conditionally permitted nonpublic classes in v1 are RFC1918, ULA, CGNAT and loopback. They still require an exact Origin, explicit method set, registry-issued client binding and narrow allowed CIDRs. IPv4 endpoints require /24 or narrower; IPv6 /64 or narrower. Loopback additionally requires `allowLoopback` plus a host /32 or /128. Configuration alone does not grant the public importer access.

IPv4-mapped IPv6 is normalized to the embedded IPv4 before provider, operator or address-class decisions. This prevents metadata and private networks from obtaining a weaker IPv6 decision. Deprecated IPv4-compatible addresses are prohibited; the more-specific unspecified and loopback classes remain distinct.

Provider hard denies win over all endpoint CIDRs. In particular, AWS IPv6 metadata lies within ULA, Alibaba metadata lies within CGNAT, and Azure WireServer has a nominal public IPv4 address. Only HTTP client targets are classified here; no host firewall advice is inferred.

IPv6 Public is positive: it must lie within a downloaded IANA `ALLOCATED` global-unicast prefix and outside all excluded special ranges. An address in `2000::/3` that lies in a gap or RESERVED range remains prohibited. The register states that unlisted space is reserved for future allocation. Data are updated deliberately during development/release, never downloaded on a web request.

## Reproduce preparation

```sh
yq -p xml -o json '.' sources/iana-ipv4-special-registry.xml > sources/iana-ipv4-special-registry.json
yq -p xml -o json '.' sources/iana-ipv6-special-registry.xml > sources/iana-ipv6-special-registry.json
yq -p xml -o json '.' sources/iana-ipv6-unicast-address-assignments.xml > sources/iana-ipv6-unicast-address-assignments.json
jq -n --slurpfile ipv4 sources/iana-ipv4-special-registry.json --slurpfile ipv6 sources/iana-ipv6-special-registry.json --slurpfile unicast sources/iana-ipv6-unicast-address-assignments.json -f scripts/derive-address-rules.jq > address-rules.json
python3 scripts/extract-ledger.py
python3 scripts/generate-corpus.py
```

Sources: [IANA IPv4 special](https://www.iana.org/assignments/iana-ipv4-special-registry/), [IANA IPv6 special](https://www.iana.org/assignments/iana-ipv6-special-registry/), [IANA IPv6 global unicast](https://www.iana.org/assignments/ipv6-unicast-address-assignments/), [AWS metadata](https://docs.aws.amazon.com/AWSEC2/latest/UserGuide/instancedata-data-retrieval.html), [Alibaba metadata](https://www.alibabacloud.com/help/en/ecs/user-guide/view-instance-metadata), [Azure platform IP](https://learn.microsoft.com/en-us/azure/virtual-network/what-is-ip-address-168-63-129-16), [RFC 4291](https://www.rfc-editor.org/rfc/rfc4291.html), [RFC 1112](https://www.rfc-editor.org/rfc/rfc1112.html).
