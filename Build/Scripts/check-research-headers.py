#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Reject captured cookie values in public-document response fixtures."""
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[2]
HEADERS = ROOT / "Resources/Private/HttpGuard/data/security-corpus/sources"

failures = []
for path in sorted(HEADERS.glob("*.headers.txt")):
    for number, line in enumerate(path.read_text(encoding="utf-8").splitlines(), 1):
        if not line.lower().startswith("set-cookie:"):
            continue
        match = re.fullmatch(r"(?i)set-cookie:\s*[^=;\s]+=(\[REDACTED\])(?:;.*)?", line)
        if match is None:
            failures.append(f"{path.relative_to(ROOT)}:{number}")
if failures:
    raise SystemExit("Unredacted response cookie at: " + ", ".join(failures))
print("Public-document response headers contain no captured cookie values.")
