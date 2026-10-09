#!/usr/bin/env python3
"""Hash-check tracked source files against the disposable native test copy."""
import hashlib
import json
from pathlib import Path
import subprocess
import sys

source, runtime = [Path(argument).resolve() for argument in sys.argv[1:3]]
paths = subprocess.check_output(["git", "-C", str(source), "ls-files", "-z"]).split(b"\0")
rows, mismatches = [], []
for raw_path in paths:
    if not raw_path:
        continue
    name = raw_path.decode()
    original = source / name
    copy = runtime / name
    if not original.is_file():
        continue
    expected = hashlib.sha256(original.read_bytes()).hexdigest()
    actual = hashlib.sha256(copy.read_bytes()).hexdigest() if copy.is_file() else None
    rows.append({"path": name, "sha256": expected})
    if actual != expected:
        mismatches.append({"path": name, "source_sha256": expected, "runtime_sha256": actual})
manifest = {"source_commit": subprocess.check_output(["git", "-C", str(source), "rev-parse", "HEAD"], text=True).strip(), "source": str(source), "runtime": str(runtime), "tracked_file_count": len(rows), "tracked_manifest_sha256": hashlib.sha256(json.dumps(rows, sort_keys=True).encode()).hexdigest(), "mismatches": mismatches, "runtime_git_note": "Empty runtime repository initialized only for dependency hook installation; source identity established by tracked-file hashes, not runtime HEAD"}
output = Path(__file__).resolve().parents[1] / "evidence" / "vault-native-source-verification.json"
output.write_text(json.dumps(manifest, indent=2) + "\n")
print(f"Verified {len(rows)} tracked files; {len(mismatches)} mismatches.")
sys.exit(bool(mismatches))
