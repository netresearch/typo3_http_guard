#!/usr/bin/env python3
"""Extract the original Markdown requirements and tests without changing scope."""
from pathlib import Path
import hashlib
import json
import re

ROOT = Path(__file__).resolve().parents[2]
OUT = Path(__file__).resolve().parents[1]
SPEC = ROOT / "http-guard-specs" / "specs"

def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()

requirements = []
for line_number, line in enumerate((SPEC / "01-product-requirements.md").read_text().splitlines(), 1):
    if re.match(r"\| HG-\d{3} \|", line):
        cells = [cell.strip() for cell in line.strip("|").split("|")]
        requirements.append({"id": cells[0], "requirement": cells[1], "source": {"path": "http-guard-specs/specs/01-product-requirements.md", "line": line_number}, "status": "not_verified"})

tests = []
declared_map = {}
for line_number, line in enumerate((SPEC / "06-verification.md").read_text().splitlines(), 1):
    if re.match(r"\| T\d{3} \|", line):
        cells = [cell.strip() for cell in line.strip("|").split("|")]
        if len(cells) != 5:
            raise ValueError(f"Unexpected test-table columns at line {line_number}: {cells!r}")
        tests.append({"id": cells[0], "priority": cells[1], "requirements": re.findall(r"HG-\d{3}", cells[2]), "scenario": cells[3], "expected_evidence": cells[4], "source": {"path": "http-guard-specs/specs/06-verification.md", "line": line_number}, "implementation_status": "not_implemented", "execution_status": "not_run", "evidence": []})
    elif re.match(r"\| HG-\d{3} \|", line):
        cells = [cell.strip() for cell in line.strip("|").split("|")]
        declared_map[cells[0]] = re.findall(r"T\d{3}", cells[1])

assert [r["id"] for r in requirements] == [f"HG-{i:03}" for i in range(1, 46)]
assert [t["id"] for t in tests] == [f"T{i:03}" for i in range(1, 85)]
req_ids = {r["id"] for r in requirements}
test_ids = {t["id"] for t in tests}
for req in requirements:
    req["minimum_test_ids"] = declared_map[req["id"]]
    req["all_matrix_test_ids"] = [test["id"] for test in tests if req["id"] in test["requirements"]]
    assert set(req["minimum_test_ids"]).issubset(test_ids)
    # The original document's minimum table is preserved even if it differs
    # from the test-row mapping; discrepancies remain explicit evidence.
    req["mapping_discrepancy"] = sorted(set(req["minimum_test_ids"]) - set(req["all_matrix_test_ids"]))
for test in tests:
    assert set(test["requirements"]).issubset(req_ids)

ledger = {"schema_version": 1, "revision": "2026-10-08-original-v1", "scope": "Exact 45 HG requirements and 84 proposed tests from the original specification; this ledger is not a test execution", "source_files": [{"path": f"http-guard-specs/specs/{name}", "sha256": digest(SPEC / name)} for name in ["01-product-requirements.md", "02-security-model.md", "04-configuration-and-api.md", "06-verification.md"]], "requirements": requirements, "tests": tests}
(OUT / "requirements-tests.json").write_text(json.dumps(ledger, ensure_ascii=False, indent=2) + "\n")

lines = ["# Original requirements and test ledger", "", "This preserves all 45 HG requirements and 84 proposed tests. No production test in this ledger has been implemented or executed by preparing these data. Legacy Vault baseline runs are separate characterization evidence, not evidence that an HG test passes.", "", f"P0: {sum(t['priority'] == 'P0' for t in tests)}. P1: {sum(t['priority'] == 'P1' for t in tests)}.", "", "## Requirements", "", "| ID | Minimum original tests | Status |", "|---|---|---|"]
lines.extend(f"| {r['id']} | {', '.join(r['minimum_test_ids'])} | not verified |" for r in requirements)
lines.extend(["", "## Tests", "", "| ID | Priority | Requirements | Execution | Scenario |", "|---|---|---|---|---|"])
lines.extend(f"| {t['id']} | {t['priority']} | {', '.join(t['requirements'])} | not run | {t['scenario']} |" for t in tests)
discrepancies = [r for r in requirements if r['mapping_discrepancy']]
lines.extend(["", "## Original mapping discrepancies", ""])
lines.extend([f"- {r['id']}: minimum table lists {', '.join(r['mapping_discrepancy'])}, while those test rows do not list this requirement." for r in discrepancies] or ["None in the minimum-to-matrix direction."])
(OUT / "requirements-tests.md").write_text("\n".join(lines) + "\n")
print(f"Extracted {len(requirements)} requirements and {len(tests)} tests; {len(discrepancies)} mapping discrepancies.")
