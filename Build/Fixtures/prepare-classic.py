#!/usr/bin/env python3
"""Prepare disposable classic TYPO3 sites from verified official tarballs and one extension ZIP."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
import tarfile
import urllib.request
import zipfile

ROOT = Path(__file__).resolve().parents[2]
RELEASES = {
    '13': ('13.4.36', '49aed89d1d90e2919c4fcc05d6fea95b8b693d3481589d74ed5215084c3fce3b'),
    '14': ('14.3.8', '55f26416c4d6b988d7a7b25aa5eb49d060044ba6dec98761ba9ec2fe9fd6caac'),
}
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--runtime', type=Path, required=True)
parser.add_argument('--archive', type=Path, required=True)
args = parser.parse_args()
runtime = args.runtime.resolve()
runtime.mkdir(parents=True, exist_ok=True)
archive = args.archive.resolve()
manifest_path = archive.with_suffix(archive.suffix + '.manifest.json')
manifest = json.loads(manifest_path.read_text())
if manifest['archive_sha256'] != hashlib.sha256(archive.read_bytes()).hexdigest():
    raise SystemExit('Extension archive differs from its builder manifest')
if manifest['extension_key'] != 'nr_http_guard' or not manifest['single_package']:
    raise SystemExit('Unexpected extension archive identity')
with zipfile.ZipFile(archive) as extension:
    if extension.testzip() is not None or set(extension.namelist()) != set(manifest['files']):
        raise SystemExit('Extension archive inventory/integrity mismatch')
    for name, checksum in manifest['files'].items():
        if Path(name).is_absolute() or '..' in Path(name).parts:
            raise SystemExit('Unsafe extension archive member')
        if hashlib.sha256(extension.read(name)).hexdigest() != checksum:
            raise SystemExit('Extension archive member differs: ' + name)
    for major, (version, checksum) in RELEASES.items():
        site = runtime / ('classic' + major)
        if site.exists():
            raise SystemExit('Use a fresh disposable site directory: ' + str(site))
        tarball = runtime / ('typo3_src-' + version + '.tar.gz')
        if not tarball.exists():
            # Only the fixed HTTPS release origin and pinned versions above; verified before extraction.
            # nosemgrep: python.lang.security.audit.dynamic-urllib-use-detected.dynamic-urllib-use-detected
            urllib.request.urlretrieve('https://get.typo3.org/' + version + '/tar.gz', tarball)
        if hashlib.sha256(tarball.read_bytes()).hexdigest() != checksum:
            raise SystemExit('Official Core tarball SHA256 mismatch: ' + version)
        source = runtime / ('typo3_src-' + version)
        if not source.exists():
            # The exact official archive passed SHA-256 verification; extraction rejects unsafe members.
            # nosemgrep: trailofbits.python.tarfile-extractall-traversal.tarfile-extractall-traversal
            with tarfile.open(tarball) as core:
                core.extractall(runtime, filter='data')
        site.mkdir()
        for name, target in [('typo3_src', source), ('typo3', source / 'typo3'),
                             ('vendor', source / 'vendor'), ('index.php', source / 'index.php')]:
            (site / name).symlink_to(target, target_is_directory=target.is_dir())
        shutil.copytree(ROOT / 'Build/Fixtures' / ('classic' + major) / 'typo3conf', site / 'typo3conf')
        target = site / 'typo3conf/ext/nr_http_guard'
        target.mkdir(parents=True)
        extension.extractall(target)
        (site / 'support').mkdir()
        shutil.copyfile(ROOT / 'Tests/Fixtures/RecordingMiddleware.php', site / 'support/RecordingMiddleware.php')
        # Use TYPO3's actual package activation and metadata-based class loading.
        activation = subprocess.run(
            ['php', str(ROOT / 'Build/Fixtures/initialize-classic.php'), str(site)],
            check=True, capture_output=True, text=True,
        )
        activation_report = json.loads(activation.stdout)
        if activation_report.get('status') != 'PASS':
            raise SystemExit('Native classic extension activation did not pass')
        print(json.dumps({'fixture': str(site), 'core': version, 'core_archive_sha256': checksum,
                          'extension_archive_sha256': manifest['archive_sha256'],
                          'extension_installed_from_zip': True, 'composer_run': False,
                          'native_activation': activation_report}), flush=True)
