#!/usr/bin/env python3
"""Build the single installable TYPO3 extension from source, without Composer."""
import argparse
import hashlib
import json
from pathlib import Path
import re
import subprocess
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

def read_git(*arguments):
    return subprocess.run(['git', '--no-replace-objects', '-C', str(ROOT), *arguments],
                          check=True, stdout=subprocess.PIPE).stdout

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--output', type=Path, required=True)
parser.add_argument('--source-revision',
                    help='Read package data from this full Git commit SHA without checking out or executing it')
arguments = parser.parse_args()
output = arguments.output.resolve()
output.parent.mkdir(parents=True, exist_ok=True)
contents = {}
if arguments.source_revision is not None:
    revision = arguments.source_revision
    if re.fullmatch(r'[0-9a-f]{40}', revision) is None:
        raise SystemExit('Source revision must be a full Git commit SHA')
    if read_git('cat-file', '-t', revision).strip() != b'commit':
        raise SystemExit('Source revision must identify a commit')
    # Read immutable Git objects only. In particular, do not check out the old
    # tree, execute its builder or apply its export-ignore packaging rules.
    entries = read_git('ls-tree', '--full-tree', '-r', '-z', revision)
    for entry in entries.split(b'\0'):
        if not entry:
            continue
        metadata, raw_name = entry.split(b'\t', 1)
        mode, kind, object_id = metadata.split(b' ')
        name = raw_name.decode('utf-8')
        if name not in FILES and name.split('/')[0] not in DIRECTORIES:
            continue
        if name.startswith(CORPUS) and name[len(CORPUS):] not in CORPUS_FILES:
            continue
        if name.startswith('/') or any(part in ('', '.', '..') for part in name.split('/')):
            raise SystemExit('Unsafe Git source path: ' + name)
        if name in contents:
            raise SystemExit('Duplicate Git source path: ' + name)
        if kind != b'blob' or mode not in (b'100644', b'100755'):
            raise SystemExit('Non-regular Git source: ' + name)
        contents[name] = read_git('cat-file', 'blob', object_id.decode('ascii'))
    if not set(FILES).issubset(contents):
        raise SystemExit('Missing required source files')
    for directory in DIRECTORIES:
        if not any(name.startswith(directory + '/') for name in contents):
            raise SystemExit('Missing required directory: ' + directory)
else:
    paths = [ROOT / name for name in FILES]
    for name in DIRECTORIES:
        directory = ROOT / name
        if directory.is_symlink() or not directory.is_dir():
            raise SystemExit('Missing required directory: ' + name)
        paths.extend(sorted(path for path in directory.rglob('*')
                            if path.is_file() and is_runtime_asset(path)))
    for path in sorted(set(paths)):
        if path.is_symlink() or not path.is_file() or not path.resolve().is_relative_to(ROOT):
            raise SystemExit('Missing or symlinked source: ' + str(path.relative_to(ROOT)))
        if path == output:
            raise SystemExit('Output must be outside packaged source directories')
        contents[str(path.relative_to(ROOT))] = path.read_bytes()
manifest = json.loads(contents['composer.json'])
if manifest.get('name') != 'netresearch/nr-http-guard':
    raise SystemExit('Unexpected extension package identity')
if 'netresearch/http-guard' in manifest.get('require', {}):
    raise SystemExit('The extension must not require a separate HTTP Guard package')
if manifest.get('extra', {}).get('typo3/cms', {}).get('extension-key') != 'nr_http_guard':
    raise SystemExit('Unexpected TYPO3 extension key')
required = ('Classes/HttpGuard/AddressClassifier.php',
            'Classes/HttpGuard/GuardConfig.php',
            'Resources/Private/HttpGuard/data/security-corpus/address-rules.json',
            'Documentation/guides.xml', 'Documentation/Index.rst')
file_map = {name: hashlib.sha256(data).hexdigest() for name, data in sorted(contents.items())}
if not set(required).issubset(file_map):
    raise SystemExit('Kernel, policy or complete documentation is missing')
with zipfile.ZipFile(output, 'w', compression=zipfile.ZIP_DEFLATED, compresslevel=9) as archive:
    for name in sorted(contents):
        item = zipfile.ZipInfo(name, date_time=(2026, 10, 9, 0, 0, 0))
        item.create_system = 3
        item.external_attr = (0o100644 << 16)
        item.compress_type = zipfile.ZIP_DEFLATED
        archive.writestr(item, contents[name], compress_type=zipfile.ZIP_DEFLATED, compresslevel=9)
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
