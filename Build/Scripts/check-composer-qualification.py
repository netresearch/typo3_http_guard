#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Check the requested runtime before CI replaces it with individual matrix pins."""
import argparse
import ast
import json
from pathlib import Path
import re

ROOT = Path(__file__).resolve().parents[2]
SDK = ('guzzlehttp/guzzle', 'guzzlehttp/promises', 'guzzlehttp/psr7')
CORE = 'typo3/cms-core'


def exact_versions(constraint):
    versions = constraint.split('||')
    if any(re.fullmatch(r'\s*\d+\.\d+\.\d+\s*', value) is None for value in versions):
        raise ValueError('runtime constraints must contain exact qualified versions')
    return {value.strip() for value in versions}


def check(root, core=None, sdk=None):
    requested = json.loads((root / 'composer.json').read_text())['require']
    manifests = [root / 'Build/Fixtures' / cell / 'composer.json'
                 for cell in ('core13g7', 'core13g8', 'core14g7', 'core14g8')]
    required = [json.loads(path.read_text())['require'] for path in manifests]
    cores = set().union(*(exact_versions(row[CORE]) for row in required))
    # This immutable SDK fixture remains the separately qualified 7.15.3 tuple;
    # it is not an installed dependency graph or a classic Core support claim.
    legacy = json.loads((root / 'verification/dependencies/combined-kernel/guzzle7ter.composer.json').read_text())['require']
    allowed = {CORE: cores}
    for package in SDK:
        allowed[package] = set().union(*(exact_versions(row[package]) for row in [*required, legacy]))
    for package, versions in allowed.items():
        if exact_versions(requested[package]) != versions:
            raise ValueError(f'{package}: requested versions differ from qualified fixtures')
    if core is not None and core not in cores:
        raise ValueError('CI-selected Core version is outside the requested qualified versions')
    if sdk is not None:
        selected = tuple(sdk)
        rows = [row for row in required if row[CORE] == core]
        if selected not in {tuple(row[package] for package in SDK) for row in rows}:
            raise ValueError('CI-selected Core/SDK tuple differs from qualified fixtures')

    classic = json.loads((root / 'Build/Fixtures/classic-tarballs.json').read_text())
    assignments = [node for node in ast.parse((root / 'Build/Fixtures/prepare-classic.py').read_text()).body
                   if isinstance(node, ast.Assign)
                   and any(isinstance(target, ast.Name) and target.id == 'RELEASES' for target in node.targets)]
    if len(assignments) != 1:
        raise ValueError('classic release declaration is ambiguous')
    release_rows = ast.literal_eval(assignments[0].value)
    releases = {row[0]: row[1] for row in release_rows.values()}
    if set(classic) != cores or set(releases) != cores:
        raise ValueError('classic release versions differ from qualified Core fixtures')
    for version in cores:
        if classic[version]['sha256'] != releases[version] or classic[version]['url'] != f'https://get.typo3.org/{version}/tar.gz':
            raise ValueError('classic release provenance differs from executable fixture')
    return {'status': 'PASS', 'requested_runtime_matches_qualified_fixtures': True,
            'core_versions': sorted(cores),
            'sdk_tuples': len({tuple(row[package] for package in SDK) for row in [*required, legacy]}),
            'classic_provenance_matches': True}


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--root', type=Path, default=ROOT)
    parser.add_argument('--core')
    parser.add_argument('--sdk', nargs=3, metavar=('GUZZLE', 'PROMISES', 'PSR7'))
    arguments = parser.parse_args()
    try:
        report = check(arguments.root, arguments.core, arguments.sdk)
    except (OSError, ValueError, KeyError, TypeError, IndexError, AttributeError, SyntaxError) as error:
        raise SystemExit(f'FAIL: Composer qualification consistency: {error}') from None
    print(json.dumps(report, sort_keys=True))


if __name__ == '__main__':
    main()
