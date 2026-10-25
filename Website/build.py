#!/usr/bin/env python3
# SPDX-License-Identifier: MIT
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Build only the public explorer assets, deriving rules from the runtime corpus."""
import hashlib
import json
from pathlib import Path
import shutil

ROOT = Path(__file__).resolve().parent.parent
SOURCE = ROOT / "Website"
OUTPUT = ROOT / ".Build/pages"
CORPUS_ROOT = ROOT / "Resources/Private/HttpGuard/data/security-corpus"


def build():
    OUTPUT.mkdir(parents=True, exist_ok=True)
    for path in OUTPUT.iterdir():
        if path.is_dir():
            shutil.rmtree(path)
        else:
            path.unlink()
    for name in ["index.html", "styles.css", "app.mjs", "engine.mjs", "cases.mjs", "i18n.mjs", "scenario.mjs"]:
        shutil.copyfile(SOURCE / name, OUTPUT / name)
    shutil.copytree(SOURCE / "assets", OUTPUT / "assets")
    raw = (CORPUS_ROOT / "address-rules.json").read_bytes()
    corpus = json.loads(raw)
    cases = json.loads((CORPUS_ROOT / "address-cases.json").read_text())["cases"]
    data = "// Generated from the versioned repository corpus; do not edit.\n"
    for name, value in [("CORPUS", corpus), ("ADDRESS_CASES", cases), ("CORPUS_SHA256", hashlib.sha256(raw).hexdigest())]:
        data += "export const " + name + " = " + json.dumps(value, ensure_ascii=True, separators=(",", ":")) + ";\n"
    (OUTPUT / "corpus.mjs").write_text(data)
    (OUTPUT / ".nojekyll").touch()
    print("Built bilingual explorer in .Build/pages from " + corpus["revision"])


if __name__ == "__main__":
    build()
