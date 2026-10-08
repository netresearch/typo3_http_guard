#!/usr/bin/env python3
"""Prepare disposable, genuine TYPO3 runtimes from the recorded exact matrix."""
import argparse
import json
import shutil
import subprocess
from pathlib import Path

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--runtime', required=True, type=Path)
parser.add_argument('--allow-disposable-fixture-advisories', action='store_true')
arguments = parser.parse_args()
package = Path(__file__).resolve().parents[2]
packages = package.parent
recorded = packages.parent / 'evidence/typo3-integration/locks'
advisories = ['PKSA-8j6w-3kr7-s9xk', 'PKSA-6j95-1jtb-x6wc', 'PKSA-cbx2-m9db-bmzd']

for name in ('core13g7', 'core13g8', 'core14g7', 'core14g8'):
    destination = arguments.runtime.resolve() / name
    destination.mkdir(parents=True, exist_ok=True)
    (destination / 'public').mkdir(exist_ok=True)
    manifest = json.loads((recorded / name / 'composer.json').read_text())
    manifest['config'].pop('policy', None)
    if arguments.allow_disposable_fixture_advisories:
        manifest['config']['policy'] = {'advisories': {'ignore-id': advisories}}
    manifest['repositories'] = [
        {'type': 'path', 'url': str(packages / component),
         'options': {'symlink': True, 'versions': {f'netresearch/{component}': 'dev-main'}}}
        for component in ('http-guard', 'nr-http-guard')
    ]
    (destination / 'composer.json').write_text(json.dumps(manifest, indent=2) + '\n')
    if not (destination / 'composer.lock').exists():
        shutil.copyfile(recorded / name / 'composer.lock', destination / 'composer.lock')
    config = destination / 'config'
    expected_config = package / 'Build/Fixtures' / name / 'config'
    if not config.exists():
        config.symlink_to(expected_config, target_is_directory=True)
    elif config.resolve() != expected_config.resolve():
        raise SystemExit(f'Refusing to reuse a different configuration: {config}')
    # Refresh only the local path-package metadata. All Core/Guzzle direct versions
    # remain exact; the original locks stay immutable in the evidence directory.
    with (destination / 'production-install.log').open('w') as log:
        result = subprocess.run(
            ['composer', 'update', 'netresearch/http-guard', 'netresearch/nr-http-guard',
             '--with-dependencies', '--working-dir=' + str(destination),
             '--no-interaction', '--prefer-dist'], stdout=log, stderr=subprocess.STDOUT,
        )
    print(f'{name}: install exit {result.returncode}', flush=True)
    if result.returncode:
        raise SystemExit(f'See {destination / "production-install.log"}; no advisory override is automatic.')
