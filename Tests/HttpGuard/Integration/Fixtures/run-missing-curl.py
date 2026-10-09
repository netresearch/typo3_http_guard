#!/usr/bin/env python3
"""Owned missing-ext-curl send probe; HTTP/TCP witness is read by the curl-capable parent."""
import argparse, hashlib, json, os, pathlib, subprocess, urllib.request

parser = argparse.ArgumentParser()
parser.add_argument("--package", required=True)
parser.add_argument("--g7-vendor", required=True)
parser.add_argument("--g8-vendor", required=True)
parser.add_argument("--evidence", required=True)
runtime = parser.add_mutually_exclusive_group(required=True)
runtime.add_argument("--image")
runtime.add_argument("--php-binary")
parser.add_argument("--php-extension", action="append", default=[])
args = parser.parse_args()
opener = urllib.request.build_opener(urllib.request.ProxyHandler({}))

def counters():
    result = {}
    for label, ip in (("public-a", "203.0.115.100"), ("public-b", "203.0.115.101"), ("private", "10.23.4.12")):
        with opener.open("http://" + ip + ":8091/stats", timeout=2) as response:
            result[label] = json.load(response)
    return result

evidence = pathlib.Path(args.evidence)
evidence.mkdir(parents=True, exist_ok=True)
results = []
for major, vendor in ((7, args.g7_vendor), (8, args.g8_vendor)):
    before = counters()
    command = ["docker", "run", "--rm", "--network", "host",
        "--mount", "type=bind,src=" + str(pathlib.Path(args.package).resolve()) + ",dst=/guard,readonly",
        "--mount", "type=bind,src=" + str(pathlib.Path(vendor).resolve()) + ",dst=/deps,readonly",
        "-e", "HTTP_GUARD_TEST_AUTOLOAD=/deps/autoload.php", args.image,
        "php", "-n", "/guard/tests/Integration/Fixtures/IsolatedCapabilityProbe.php", "missing_curl"]
    environment = None
    if args.php_binary:
        command = [args.php_binary, "-n"]
        for extension in args.php_extension:
            command.extend(["-d", "extension=" + extension])
        command.extend([str(pathlib.Path(args.package).resolve() / "tests/Integration/Fixtures/IsolatedCapabilityProbe.php"), "missing_curl"])
        environment = dict(os.environ, HTTP_GUARD_TEST_AUTOLOAD=str(pathlib.Path(vendor).resolve() / "autoload.php"))
    completed = subprocess.run(command, capture_output=True, text=True, timeout=30, env=environment)
    after = counters()
    try:
        child = json.loads(completed.stdout)
    except (ValueError, TypeError):
        child = {"status": "FAIL", "stdout": completed.stdout}
    zero = all(before[label][key] == after[label][key] for label in before for key in ("tcp", "requests"))
    good = completed.returncode == 0 and child.get("status") == "PASS" and child.get("curlLoaded") is False and child.get("curlMultiAvailable") is False and child.get("reasonCode") == "transport_unsupported" and child.get("transport", {}).get("nativeConstructed") == 0 and zero
    record = {"status": "PASS" if good else "FAIL", "major": major, "image": args.image, "phpBinary": args.php_binary,
        "command": command, "exitCode": completed.returncode, "stderr": completed.stderr,
        "child": child, "zeroNewTcpAndHttp": zero, "parentBefore": before, "parentAfter": after,
        "fixtureSha256": hashlib.sha256((pathlib.Path(args.package) / "tests/Integration/Fixtures/IsolatedCapabilityProbe.php").read_bytes()).hexdigest()}
    (evidence / ("guzzle" + str(major) + ".json")).write_text(json.dumps(record, indent=2) + "\n")
    results.append({"major": major, "status": record["status"], "php": child.get("phpVersion"),
        "curlLoaded": child.get("curlLoaded"), "nativeConstructed": child.get("transport", {}).get("nativeConstructed"), "zeroNewTcpAndHttp": zero})
    print(json.dumps(results[-1]), flush=True)
summary = {"status": "PASS" if all(row["status"] == "PASS" for row in results) else "FAIL", "results": results}
(evidence / "summary.json").write_text(json.dumps(summary, indent=2) + "\n")
raise SystemExit(0 if summary["status"] == "PASS" else 1)
