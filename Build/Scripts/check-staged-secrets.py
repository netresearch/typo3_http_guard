#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Scan exact changed index blobs, without reading credentials from the worktree."""
import base64
import hashlib
import json
from pathlib import Path
import re
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
# Exact public fixture digests: pairs avoid resembling a key assignment to
# the credential detector when this checker itself is staged.
SYNTHETIC = dict([
    (b'Tests/HttpGuard/Integration/certificates/ca.key', '352c03de1c19e4a8eb803db73713d38d9c23cd658862db07529ba6f233eee592'),
    (b'Tests/HttpGuard/Integration/certificates/client.key', 'e1a150875e3ef0e3117a9ef5c7969b64448c279b6d1cfd7a0f044478fa3c9e06'),
    (b'Tests/HttpGuard/Integration/certificates/server.key', 'ae691852afc79c13ba926ab28cd31c26b3aaeded2be5fa657ff624629ac74269'),
])


def git(*args):
    result = subprocess.run(['git', '--no-replace-objects', '--literal-pathspecs', *args],
                            cwd=ROOT, capture_output=True, check=False)
    if result.returncode != 0:
        raise RuntimeError('Git index unavailable')
    return result.stdout


def check():
    if git('ls-files', '--unmerged', '-z'):
        raise RuntimeError('Resolve the unmerged index before committing')
    paths = git('diff', '--cached', '--name-only', '--no-renames', '--diff-filter=ACMRT', '-z', '--').split(b'\0')
    blobs = []
    scanned = []
    for path in filter(None, paths):
        records = git('ls-files', '--stage', '-z', '--', path).split(b'\0')
        records = [record for record in records if record]
        if len(records) != 1:
            raise RuntimeError('Ambiguous staged entry')
        header, actual_path = records[0].split(b'\t', 1)
        mode, object_id, stage = header.split(b' ')
        if actual_path != path or stage != b'0' or not re.fullmatch(b'[0-9a-f]{40,64}', object_id):
            raise RuntimeError('Invalid staged entry')
        if mode == b'160000':
            raise RuntimeError('Submodules need separate secret qualification')
        blob = git('cat-file', 'blob', object_id)
        expected = SYNTHETIC.get(path)
        if expected is not None and hashlib.sha256(blob).hexdigest() == expected:
            continue
        blobs.append(base64.b64encode(blob).decode('ascii'))
        scanned.append(path.decode('utf-8', errors='surrogateescape'))
    # Tool availability is checked even for an empty stage; failures never pass.
    result = subprocess.run(
        ['php', str(ROOT / 'Build/Scripts/detect-staged-secret.php')],
        cwd=ROOT, input=json.dumps(blobs).encode(), capture_output=True, check=False,
    )
    if result.returncode != 0:
        raise RuntimeError('Secret detector unavailable; install development dependencies')
    detected = json.loads(result.stdout)
    if not isinstance(detected, list) or len(detected) != len(scanned) or any(type(item) is not bool for item in detected):
        raise RuntimeError('Invalid secret detector response')
    failures = [path for path, found in zip(scanned, detected) if found]
    if failures:
        print(json.dumps({'status': 'fail', 'paths': failures}), file=sys.stderr)
        return 1
    print(json.dumps({'status': 'pass', 'scanned_index_blobs': len(scanned)}))
    return 0


if __name__ == '__main__':
    try:
        sys.exit(check())
    except (OSError, RuntimeError, ValueError, IndexError):
        print('Staged secret check failed; verify tools and index integrity. No secret values are printed.', file=sys.stderr)
        sys.exit(2)
