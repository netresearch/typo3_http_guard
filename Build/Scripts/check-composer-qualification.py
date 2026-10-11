#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Validate supported package ranges and reproducible CI fixture selections."""
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


def version_tuple(version):
    if re.fullmatch(r'\d+\.\d+\.\d+', version) is None:
        raise ValueError('expected a stable three-component version')
    return tuple(map(int, version.split('.')))


def caret_ranges(constraint):
    # Project manifests use the documented caret notation. This is deliberately
    # not a replacement for Composer's general-purpose constraint parser.
    ranges = []
    for value in constraint.split('||'):
        match = re.fullmatch(r'\s*\^(\d+)\.(\d+)(?:\.(\d+))?\s*', value)
        if match is None:
            raise ValueError('package support must use caret ranges')
        lower = tuple(int(part or 0) for part in match.groups())
        if lower[0] == 0:
            raise ValueError('runtime packages require stable major versions')
        ranges.append((lower, (lower[0] + 1, 0, 0)))
    return ranges


def contains(ranges, version):
    parsed = version_tuple(version)
    return any(lower <= parsed < upper for lower, upper in ranges)


def selected_range(ranges, selection):
    if selection.startswith('^'):
        selected = caret_ranges(selection)
        if len(selected) != 1 or not any(
                lower <= selected[0][0] and selected[0][1] <= upper
                for lower, upper in ranges):
            raise ValueError('CI-selected range is outside package support')
        return selected[0][0]
    if not contains(ranges, selection):
        raise ValueError('CI-selected version is outside package support')
    return version_tuple(selection)


def check(root, core=None, sdk=None):
    requested = json.loads((root / 'composer.json').read_text())['require']
    manifests = [root / 'Build/Fixtures' / cell / 'composer.json'
                 for cell in ('core13g7', 'core13g8', 'core14g7', 'core14g8')]
    required = [json.loads(path.read_text())['require'] for path in manifests]
    cores = set().union(*(exact_versions(row[CORE]) for row in required))
    # This immutable SDK fixture remains the separately qualified 7.15.3 tuple;
    # it is not an installed dependency graph or a classic Core support claim.
    legacy = json.loads((root / 'Build/Fixtures/HistoricalSdk/guzzle7ter.json').read_text())['require']
    recorded = {CORE: cores}
    for package in SDK:
        recorded[package] = set().union(*(exact_versions(row[package]) for row in [*required, legacy]))
    floors = {
        CORE: {13: (13, 4, 36), 14: (14, 3, 8)},
        SDK[0]: {7: (7, 15, 2), 8: (8, 2, 0)},
        SDK[1]: {2: (2, 5, 1), 3: (3, 0, 2)},
        SDK[2]: {2: (2, 13, 0), 3: (3, 1, 0)},
    }
    declared = {}
    for package, versions in recorded.items():
        ranges = caret_ranges(requested[package])
        if {lower[0] for lower, _ in ranges} != set(floors[package]):
            raise ValueError(f'{package}: unsupported or missing major family')
        if any(lower < floors[package][lower[0]] for lower, _ in ranges):
            raise ValueError(f'{package}: range includes an unsupported dependency floor')
        if not all(contains(ranges, version) for version in versions):
            raise ValueError(f'{package}: recorded fixtures fall outside package support')
        declared[package] = ranges
    if core is not None:
        selected_range(declared[CORE], core)
    if sdk is not None:
        selected = [selected_range(declared[package], value)
                    for package, value in zip(SDK, sdk)]
        if tuple(value[0] for value in selected) not in {(7, 2, 2), (8, 3, 3)}:
            raise ValueError('CI-selected SDK major families are incompatible')

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
    return {'status': 'PASS', 'package_ranges_include_recorded_fixtures': True,
            'recorded_core_versions': sorted(cores),
            'recorded_sdk_tuples': len({tuple(row[package] for package in SDK) for row in [*required, legacy]}),
            'selected_runtime_within_supported_ranges': True if core is not None or sdk is not None else None,
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
