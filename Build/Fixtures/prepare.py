#!/usr/bin/env python3
"""Prepare disposable, genuine TYPO3 runtimes from the active exact matrix."""
import argparse
import json
import subprocess
from pathlib import Path

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--runtime', required=True, type=Path)
arguments = parser.parse_args()
package = Path(__file__).resolve().parents[2]

for name in ('core13g7', 'core13g8', 'core14g7', 'core14g8'):
    destination = arguments.runtime.resolve() / name
    destination.mkdir(parents=True, exist_ok=True)
    (destination / 'public').mkdir(exist_ok=True)
    manifest = json.loads((package / 'Build/Fixtures' / name / 'composer.json').read_text())
    manifest['repositories'] = [
        {'type': 'path', 'url': str(package),
         'options': {'symlink': True, 'versions': {'netresearch/nr-http-guard': 'dev-main'}}}
    ]
    (destination / 'composer.json').write_text(json.dumps(manifest, indent=2) + '\n')
    config = destination / 'config'
    expected_config = package / 'Build/Fixtures' / name / 'config'
    if not config.exists():
        config.symlink_to(expected_config, target_is_directory=True)
    elif config.resolve() != expected_config.resolve():
        raise SystemExit(f'Refusing to reuse a different configuration: {config}')
    # Resolve the active exact Core/SDK matrix. Historical lock records are never
    # installation inputs; Composer's advisory blocking remains enabled.
    with (destination / 'production-install.log').open('w') as log:
        result = subprocess.run(
            ['composer', 'update', '--working-dir=' + str(destination),
             '--no-interaction', '--prefer-dist'], stdout=log, stderr=subprocess.STDOUT,
        )
    print(f'{name}: install exit {result.returncode}', flush=True)
    if result.returncode:
        raise SystemExit(f'See {destination / "production-install.log"}; dependency qualification failed.')
