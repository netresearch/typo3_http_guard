#!/usr/bin/env python3
"""Generate precise binary CIDR fixtures from jq-normalized registry data.

This is a test-data generator, not a network guard. It never sends requests.
JSON is parsed structurally; CIDR arithmetic uses Python's ipaddress library.
"""
import ipaddress
import json
from pathlib import Path

OUT = Path(__file__).resolve().parents[1]
rules = json.loads((OUT / "address-rules.json").read_text())
special = [(ipaddress.ip_network(r["cidr"]), r) for r in rules["iana_special_rules"] + rules["supplemental_rules"]]
hard = [(ipaddress.ip_network(r["cidr"]), r) for r in rules["provider_hard_denies"]]
allocated = [ipaddress.ip_network(r["cidr"]) for r in rules["ipv6_allocated_global_prefixes"]]

def classify(value):
    ip = ipaddress.ip_address(value)
    mapped = ip.ipv4_mapped if ip.version == 6 else None
    if mapped is not None:
        ip = mapped
    for network, rule in hard:
        if ip.version == network.version and ip in network:
            return {"public_decision": "deny", "address_class": rule["class"], "normalized_address": str(ip), "endpoint_rule": "forbidden", "matched_cidr": rule["cidr"], "mapped_ipv4": mapped is not None}
    matches = [(network, rule) for network, rule in special if ip.version == network.version and ip in network and rule["class"] != "ipv4_mapped_normalize"]
    if matches:
        network, rule = max(matches, key=lambda item: item[0].prefixlen)
        return {"public_decision": "deny", "address_class": rule["class"], "normalized_address": str(ip), "endpoint_rule": rule["endpoint"], "matched_cidr": rule["cidr"], "mapped_ipv4": mapped is not None}
    if ip.version == 6 and not any(ip in network for network in allocated):
        return {"public_decision": "deny", "address_class": "unallocated_or_non_global_ipv6", "normalized_address": str(ip), "endpoint_rule": "forbidden", "matched_cidr": None, "mapped_ipv4": False}
    return {"public_decision": "allow", "address_class": "ordinary_global_unicast", "normalized_address": str(ip), "endpoint_rule": "requires_bound_endpoint_and_narrow_cidr", "matched_cidr": None, "mapped_ipv4": mapped is not None}

address_cases = []
for index, rule in enumerate(rules["iana_special_rules"] + rules["supplemental_rules"] + rules["provider_hard_denies"], 1):
    network = ipaddress.ip_network(rule["cidr"])
    positions = [("first", int(network.network_address)), ("last", int(network.broadcast_address))]
    if int(network.network_address) > 0:
        positions.append(("before", int(network.network_address) - 1))
    if int(network.broadcast_address) < (1 << network.max_prefixlen) - 1:
        positions.append(("after", int(network.broadcast_address) + 1))
    for position, integer in positions:
        value = str(ipaddress.ip_address(integer)) if network.version == 4 else str(ipaddress.IPv6Address(integer))
        address_cases.append({"id": f"ADDR-{index:03}-{position}", "type": "address_classification", "anchor_cidr": rule["cidr"], "position": position, "input": {"address": value}, "expected": classify(value), "test_ids": ["T020"], "requirement_ids": ["HG-010", "HG-011"], "execution_status": "not_run"})

extra = ["8.8.8.8", "1.1.1.1", "2001:4860:4860::8888", "2606:4700:4700::1111", "2000::1", "2001:1000::1", "3000::1", "4000::1", "fec0::1", "::ffff:8.8.8.8", "::ffff:10.23.4.12", "::ffff:127.0.0.1", "::ffff:169.254.169.254", "::ffff:100.100.100.200", "::ffff:168.63.129.16", "0:0:0:0:0:ffff:a17:40c", "::8.8.8.8", "64:ff9b::808:808", "64:ff9b:1::a00:1", "2002:a00:1::", "2001::a00:1", "192.0.0.9", "192.31.196.1", "2001:1::1", "2001:4:112::1", "fd00:ec2::254", "fd00:ec2::253", "fd00:ec2::255", "100.100.100.199", "100.100.100.201", "168.63.129.15", "168.63.129.17"]
for index, value in enumerate(extra, 1):
    address_cases.append({"id": f"ADDR-EXTRA-{index:03}", "type": "address_classification", "input": {"address": value}, "expected": classify(value), "test_ids": ["T016", "T017", "T018", "T019", "T020"], "requirement_ids": ["HG-010", "HG-011"], "execution_status": "not_run"})

def dump(name, cases, purpose):
    (OUT / name).write_text(json.dumps({"schema_version": 1, "revision": rules["revision"], "purpose": purpose, "network_access": "none; fixtures only", "cases": cases}, ensure_ascii=False, indent=2) + "\n")

dump("address-cases.json", address_cases, "Every bundled special, supplemental and provider range has binary first/last/adjacent fixtures. Expected values are proposed normative test data, not run results.")

uri_cases = []
invalid_uris = [
    ("file:///etc/passwd", "scheme_forbidden", "T009"), ("gopher://api.example.test/", "scheme_forbidden", "T009"), ("ftp://api.example.test/", "scheme_forbidden", "T009"), ("dict://api.example.test/", "scheme_forbidden", "T009"), ("/relative/path", "invalid_target", "T009"), ("//api.example.test/path", "invalid_target", "T009"),
    ("https://user:password@api.example.test/", "invalid_target", "T009"), ("https://api.example.test/#part", "invalid_target", "T009"), ("https://api.example.test/#", "invalid_target", "T009"),
    ("https://api..example.test/", "invalid_target", "T010"), ("https://.api.example.test/", "invalid_target", "T010"), ("https://api.example.test../", "invalid_target", "T010"), ("https://api%2eexample.test/", "invalid_target", "T010"), ("https://api.example.test\\@127.0.0.1/", "invalid_target", "T010"), ("https://api.example.test\n/", "invalid_target", "T010"), ("https://api.example.test\x00/", "invalid_target", "T010"), ("https://api .example.test/", "invalid_target", "T010"),
    ("http://2130706433/", "invalid_target", "T011"), ("http://0x7f000001/", "invalid_target", "T011"), ("http://0177.0.0.1/", "invalid_target", "T011"), ("http://127.1/", "invalid_target", "T011"), ("http://127.0.1/", "invalid_target", "T011"), ("http://127.000.0.1/", "invalid_target", "T011"),
    ("http://[fe80::1%25eth0]/", "invalid_target", "T012"), ("http://[::1/", "invalid_target", "T012"), ("http://::1/", "invalid_target", "T012"), ("https://api.example.test:0/", "invalid_target", "T010"), ("https://api.example.test:65536/", "invalid_target", "T010"), ("https://täst.example/", "invalid_target", "T010")
]
for index, (uri, reason, test_id) in enumerate(invalid_uris, 1):
    uri_cases.append({"id": f"URI-DENY-{index:03}", "type": "target_normalization", "input": {"raw_uri": uri}, "expected": {"decision": "deny", "reason": reason, "dns_queries": 0, "transport_calls": 0, "tcp_connections": 0, "http_requests": 0}, "test_ids": [test_id], "execution_status": "not_run"})
for index, (uri, scheme, host, port) in enumerate([("HTTPS://API.Example.Test.:443/path?q=synthetic", "https", "api.example.test", 443), ("https://xn--tst-qla.example:8443/", "https", "xn--tst-qla.example", 8443), ("http://8.8.8.8:80/", "http", "8.8.8.8", 80), ("https://[2001:4860:4860::8888]/", "https", "2001:4860:4860::8888", 443)], 1):
    uri_cases.append({"id": f"URI-NORMALIZE-{index:03}", "type": "target_normalization", "input": {"raw_uri": uri}, "expected": {"normalization": "allow_syntax_only", "scheme": scheme, "host": host, "effective_port": port, "authorization": "must_still_evaluate_policy_and_dns"}, "test_ids": ["T008"], "execution_status": "not_run"})
dump("uri-cases.json", uri_cases, "Raw-target normalization fixtures preserve ambiguity before a PSR-7 parser may erase it. Syntax acceptance is not send authorization.")

endpoint_cases = []
def endpoint_case(identifier, address, cidrs, allow_loopback, expected, reason, bound=True, operator_denied=None):
    endpoint_cases.append({"id": identifier, "type": "endpoint_policy", "input": {"address": address, "origin": "https://synthetic-endpoint.test:8443", "method": "POST", "allowed_cidrs": cidrs, "allow_loopback": allow_loopback, "registry_issued_client_binding": bound, "operator_denied_cidrs": operator_denied or []}, "expected": {"decision": expected, "reason": reason, "no_network_assertion": expected == "deny"}, "test_ids": ["T017", "T021", "T027", "T034", "T036", "T037"], "execution_status": "not_run"})
endpoint_case("EP-RFC1918-NARROW", "10.23.4.12", ["10.23.4.12/32"], False, "allow_policy_only", None)
endpoint_case("EP-ULA-NARROW", "fd12:3456:789a::12", ["fd12:3456:789a::/64"], False, "allow_policy_only", None)
endpoint_case("EP-CGNAT-NARROW", "100.100.100.199", ["100.100.100.0/24"], False, "allow_policy_only", None)
endpoint_case("EP-PRIVATE-UNBOUND", "10.23.4.12", ["10.23.4.12/32"], False, "deny", "address_forbidden", bound=False)
endpoint_case("EP-PRIVATE-WIDE", "10.23.4.12", ["10.0.0.0/8"], False, "deny", "configuration_invalid")
endpoint_case("EP-ULA-WIDE", "fd12:3456:789a::12", ["fc00::/7"], False, "deny", "configuration_invalid")
endpoint_case("EP-CGNAT-METADATA", "100.100.100.200", ["100.100.100.0/24"], False, "deny", "address_forbidden")
endpoint_case("EP-ULA-METADATA", "fd00:ec2::254", ["fd00:ec2::/64"], False, "deny", "address_forbidden")
endpoint_case("EP-AZURE-PUBLIC-METADATA", "168.63.129.16", ["168.63.129.16/32"], False, "deny", "address_forbidden")
endpoint_case("EP-LINKLOCAL-METADATA", "169.254.169.254", ["169.254.169.254/32"], False, "deny", "address_forbidden")
endpoint_case("EP-LOOPBACK-FLAG-HOST", "127.0.0.1", ["127.0.0.1/32"], True, "allow_policy_only", None)
endpoint_case("EP-LOOPBACK-NO-FLAG", "127.0.0.1", ["127.0.0.1/32"], False, "deny", "configuration_invalid")
endpoint_case("EP-LOOPBACK-WIDE", "127.0.0.1", ["127.0.0.0/24"], True, "deny", "configuration_invalid")
endpoint_case("EP-LOOPBACK-V6", "::1", ["::1/128"], True, "allow_policy_only", None)
endpoint_case("EP-LOOPBACK-V6-NO-FLAG", "::1", ["::1/128"], False, "deny", "configuration_invalid")
endpoint_case("EP-DOC-NO-GENERIC-EXCEPTION", "192.0.2.1", ["192.0.2.1/32"], False, "deny", "address_forbidden")
endpoint_case("EP-UNALLOCATED-V6", "3000::1", ["3000::/64"], False, "deny", "address_forbidden")
endpoint_case("EP-HARD-OPERATOR-DENY", "10.23.4.12", ["10.23.4.12/32"], False, "deny", "address_forbidden", operator_denied=["10.23.4.12/32"])
endpoint_case("EP-OTHER-IP-IN-ANSWER", "10.23.4.13", ["10.23.4.12/32"], False, "deny", "endpoint_mismatch")
endpoint_case("EP-PUBLIC-OUTSIDE-GRANT", "8.8.8.8", ["10.23.4.12/32"], False, "deny", "endpoint_mismatch")
dump("endpoint-cases.json", endpoint_cases, "Narrow endpoint and hard-deny composition fixtures; allowed policy cases still require fresh resolution and controlled transport.")
print(f"Prepared {len(address_cases)} address cases, {len(uri_cases)} URI cases and {len(endpoint_cases)} endpoint cases; no tests were run.")
