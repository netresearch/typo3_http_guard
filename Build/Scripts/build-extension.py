#!/usr/bin/env python3
"""Build the single installable TYPO3 extension from source, without Composer."""
import argparse
import hashlib
import json
from pathlib import Path
import zipfile

ROOT = Path(__file__).resolve().parents[2]
FILES = ('composer.json', 'ext_emconf.php', 'ext_localconf.php', 'README.md',
         'LICENSE.txt', 'LICENSE-HttpGuard.txt', 'LICENSES.md')
DIRECTORIES = ('Classes', 'Configuration', 'Documentation', 'Resources')
CORPUS = 'Resources/Private/HttpGuard/data/security-corpus/'
CORPUS_FILES = {'address-rules.json', 'address-cases.json', 'endpoint-cases.json',
                'uri-cases.json', 'scenario-cases.json', 'sources.json', 'README.md'}

def is_runtime_asset(path):
    name = str(path.relative_to(ROOT))
    if name.startswith(CORPUS):
        return name[len(CORPUS):] in CORPUS_FILES
    return True

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--output', type=Path, required=True)
arguments = parser.parse_args()
output = arguments.output.resolve()
output.parent.mkdir(parents=True, exist_ok=True)
manifest = json.loads((ROOT / 'composer.json').read_text())
if manifest.get('name') != 'netresearch/nr-http-guard':
    raise SystemExit('Unexpected extension package identity')
if 'netresearch/http-guard' in manifest.get('require', {}):
    raise SystemExit('The extension must not require a separate HTTP Guard package')
if manifest.get('extra', {}).get('typo3/cms', {}).get('extension-key') != 'nr_http_guard':
    raise SystemExit('Unexpected TYPO3 extension key')
paths = [ROOT / name for name in FILES]
for name in DIRECTORIES:
    directory = ROOT / name
    if not directory.is_dir():
        raise SystemExit('Missing required directory: ' + name)
    paths.extend(sorted(path for path in directory.rglob('*')
                        if path.is_file() and is_runtime_asset(path)))
paths = sorted(set(paths))
for path in paths:
    if path.is_symlink() or not path.is_file():
        raise SystemExit('Missing or symlinked source: ' + str(path.relative_to(ROOT)))
    if path == output:
        raise SystemExit('Output must be outside packaged source directories')
required = ('Classes/HttpGuard/AddressClassifier.php',
            'Classes/HttpGuard/GuardConfig.php',
            'Resources/Private/HttpGuard/data/security-corpus/address-rules.json',
            'Documentation/guides.xml', 'Documentation/Index.rst')
file_map = {str(path.relative_to(ROOT)): hashlib.sha256(path.read_bytes()).hexdigest()
            for path in paths}
if not set(required).issubset(file_map):
    raise SystemExit('Kernel, policy or complete documentation is missing')
with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    for path in paths:
        name = str(path.relative_to(ROOT))
        item = zipfile.ZipInfo(name, date_time=(2026, 10, 9, 0, 0, 0))
        item.create_system = 3
        item.external_attr = (0o100644 << 16)
        item.compress_type = zipfile.ZIP_DEFLATED
        archive.writestr(item, path.read_bytes(), compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
with zipfile.ZipFile(output) as archive:
    if archive.testzip() is not None:
        raise SystemExit('ZIP integrity failure')
    for name, checksum in file_map.items():
        if hashlib.sha256(archive.read(name)).hexdigest() != checksum:
            raise SystemExit('ZIP content differs: ' + name)
record = {'package': manifest['name'], 'extension_key': 'nr_http_guard',
          'composer_required_to_build': False,
          'single_package': True, 'file_count': len(file_map),
          'archive_sha256': hashlib.sha256(output.read_bytes()).hexdigest(),
          'files': file_map}
output.with_suffix(output.suffix + '.manifest.json').write_text(json.dumps(record, indent=2) + '\n')
print(json.dumps({key: value for key, value in record.items() if key != 'files'}, indent=2))
