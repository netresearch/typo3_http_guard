#!/usr/bin/env python3
"""Verify the exact public synthetic TLS fixtures before narrow scanner exclusions."""
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH

import argparse
import errno
import hashlib
import json
from pathlib import Path
import subprocess
import sys

ROOT = Path(__file__).resolve().parents[2]
FIXTURE_DIRECTORY = Path('Tests/HttpGuard/Integration/certificates')
# Tuple pairs keep exact public digests from resembling credential assignments.
EXPECTED = dict([
    ('ca.key', '352c03de1c19e4a8eb803db73713d38d9c23cd658862db07529ba6f233eee592'),
    ('client.key', 'e1a150875e3ef0e3117a9ef5c7969b64448c279b6d1cfd7a0f044478fa3c9e06'),
    ('server.key', 'ae691852afc79c13ba926ab28cd31c26b3aaeded2be5fa657ff624629ac74269'),
    ('ca.crt', 'af8f3c497f04692454e65ca6ec85ed9714acf1bbaa3d77ea046527a0c61d1506'),
    ('client.crt', '8068b4b85387837623d44a79df8fb89359a82d727828fcea33031dfa8821d80e'),
    ('server.crt', '8192115901730d167db25b0b1f7c4eb54adbe3ba585e1be055ee14de8ebe0b35'),
])
SUBJECTS = {
    'ca': 'CN=HTTP Guard SYNTHETIC TEST CA',
    'client': 'CN=HTTP Guard SYNTHETIC TEST CLIENT',
    'server': 'CN=guard.test',
}


class FixtureError(Exception):
    """A fixed diagnostic label that never carries key or command output."""


def openssl(*arguments):
    """Use argv without a shell and keep all OpenSSL output private on failure."""
    try:
        result = subprocess.run(
            ['openssl', *map(str, arguments)],
            capture_output=True,
            check=False,
            timeout=10,
        )
    except (OSError, subprocess.TimeoutExpired) as error:
        raise FixtureError('certificate verification unavailable') from error
    if result.returncode != 0:
        raise FixtureError('certificate verification failed')
    return result.stdout


def check(root):
    ancestor = root
    for part in FIXTURE_DIRECTORY.parts:
        ancestor = ancestor / part
        if ancestor.is_symlink():
            raise FixtureError('fixture directory ancestry is symlinked')
    directory = root / FIXTURE_DIRECTORY
    if directory.is_symlink() or not directory.is_dir():
        raise FixtureError('fixture directory is missing or symlinked')
    # Opengrep matches these CLI exclusions by path suffix, including directories.
    # Reject additional matching paths before the three fixture exclusions apply.
    excluded = {name: FIXTURE_DIRECTORY / name
                for name in ('ca.key', 'client.key', 'server.key')}
    for path in root.rglob('*'):
        expected = excluded.get(path.name)
        if expected is None:
            continue
        relative = path.relative_to(root)
        if (relative.parts[-len(expected.parts):] == expected.parts
                and relative != expected):
            raise FixtureError('additional scanner-excluded suffix path')
    # A new key needs review and remains visible to scanning. Only these three
    # exact paths may be excluded after this guard passes; no directory exclusion.
    key_names = {path.relative_to(directory).as_posix()
                 for path in directory.rglob('*')
                 if path.suffix.lower() in {'.key', '.pem'}}
    if key_names != {'ca.key', 'client.key', 'server.key'}:
        raise FixtureError('fixture key inventory changed')
    for name, expected in EXPECTED.items():
        path = directory / name
        if path.is_symlink() or not path.is_file():
            raise FixtureError('fixed fixture is missing or symlinked')
        try:
            actual = hashlib.sha256(path.read_bytes()).hexdigest()
        except OSError as error:
            raise FixtureError('fixed fixture cannot be read') from error
        if actual != expected:
            raise FixtureError('fixed fixture bytes changed')

    for identity, subject in SUBJECTS.items():
        certificate = directory / (identity + '.crt')
        metadata = openssl('x509', '-in', certificate, '-noout', '-subject',
                           '-issuer', '-nameopt', 'RFC2253').decode('ascii').splitlines()
        if metadata != ['subject=' + subject, 'issuer=' + SUBJECTS['ca']]:
            raise FixtureError('synthetic certificate identity changed')
        certificate_public = openssl('x509', '-in', certificate, '-pubkey', '-noout')
        key_public = openssl('pkey', '-in', directory / (identity + '.key'), '-pubout')
        if certificate_public != key_public:
            raise FixtureError('fixture certificate and key differ')

    constraints = openssl('x509', '-in', directory / 'ca.crt', '-noout',
                          '-ext', 'basicConstraints')
    if b'CA:TRUE' not in constraints:
        raise FixtureError('synthetic CA constraint is missing')
    openssl('verify', '-CAfile', directory / 'ca.crt',
            directory / 'ca.crt', directory / 'client.crt', directory / 'server.crt')
    return {
        'status': 'PASS',
        'fixtures': ['ca', 'client', 'server'],
        'exact_key_files': 3,
        'exact_certificate_files': 3,
        'synthetic_ca': SUBJECTS['ca'],
        'public_keys_match': True,
        'certificate_chain_verified': True,
    }


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root', type=Path, default=ROOT,
                        help='Source root; defaults to this script\'s repository')
    args = parser.parse_args()
    try:
        result = check(args.root.resolve())
    except OSError as error:
        # Use only a fixed platform errno name, never exception messages or paths.
        code = errno.errorcode.get(error.errno) if type(error.errno) is int else None
        print('FAIL: synthetic fixture integrity check (filesystem '
              + (code or 'unknown') + ')', file=sys.stderr)
        return 1
    except (FixtureError, UnicodeError):
        # Never print the failed bytes, digest, path supplied by a caller, public
        # key, OpenSSL stderr, or exception value.
        print('FAIL: synthetic fixture integrity check', file=sys.stderr)
        return 1
    print(json.dumps(result, sort_keys=True))
    return 0


if __name__ == '__main__':
    sys.exit(main())
