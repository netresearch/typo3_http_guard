"""Regression tests for reading released package data without executing it."""
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest
import zipfile


BUILDER = Path(__file__).resolve().parents[2] / 'Build/Scripts/build-extension.py'


class ReleasedPackageDataTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory()
        self.addCleanup(self.temporary.cleanup)
        self.directory = Path(self.temporary.name)
        self.source = self.directory / 'source'
        self.source.mkdir()
        self.files = {
            'composer.json': json.dumps({
                'name': 'netresearch/nr-http-guard',
                'extra': {'typo3/cms': {'extension-key': 'nr_http_guard'}},
            }).encode(),
            'ext_emconf.php': b'<?php // fixture\n',
            'ext_localconf.php': b'<?php // fixture\n',
            'README.md': b'Historical package data\n',
            'LICENSE.txt': b'GPL fixture\n',
            'LICENSE-HttpGuard.txt': b'MIT fixture\n',
            'LICENSES.md': b'License attribution fixture\n',
            'Classes/HttpGuard/AddressClassifier.php': b'<?php // fixture\n',
            'Classes/HttpGuard/GuardConfig.php': b'<?php // fixture\n',
            'Configuration/Services.yaml': b'services: {}\n',
            'Documentation/guides.xml': b'<guides/>\n',
            'Documentation/Index.rst': b'Fixture manual\n',
            'Resources/Private/HttpGuard/data/security-corpus/address-rules.json': b'{}\n',
            'Resources/binary-fixture.bin': b'first\n\x00second\n\xffthird',
        }
        for name, data in self.files.items():
            path = self.source / name
            path.parent.mkdir(parents=True, exist_ok=True)
            path.write_bytes(data)
        old_builder = self.source / 'Build/Scripts/build-extension.py'
        old_builder.parent.mkdir(parents=True)
        old_builder.write_text(
            "from pathlib import Path\n"
            "Path('executed-release-code').write_text('unsafe')\n"
            "raise SystemExit('Historical source code must not run')\n"
        )
        (self.source / '.gitattributes').write_text('* export-ignore\n')
        self.git('init', '--quiet')
        self.git('config', 'user.name', 'Package test fixture')
        self.git('config', 'user.email', 'fixture@example.invalid')
        self.git('config', 'commit.gpgsign', 'false')
        self.revision = self.commit()
        # The executable is the current trusted verifier, while the historical
        # Git blob above remains malicious and must be treated as data only.
        old_builder.write_bytes(BUILDER.read_bytes())
        self.builder = old_builder
        self.output = self.directory / 'package.zip'

    def git(self, *arguments, data=None):
        return subprocess.run(
            ['git', '-C', str(self.source), *arguments], input=data,
            stdout=subprocess.PIPE, stderr=subprocess.PIPE, check=True,
        ).stdout.strip()

    def commit(self, already_staged=False):
        if not already_staged:
            self.git('add', '--all')
        self.git('commit', '--quiet', '-m', 'Isolated package fixture')
        return self.git('rev-parse', 'HEAD').decode()

    def build(self, revision=None):
        self.output.unlink(missing_ok=True)
        return subprocess.run(
            ['python3', str(self.builder), '--source-revision',
             revision or self.revision, '--output', str(self.output)],
            cwd=self.source, stdout=subprocess.PIPE, stderr=subprocess.PIPE,
        )

    def read_package(self):
        with zipfile.ZipFile(self.output) as archive:
            return {name: archive.read(name) for name in archive.namelist()}

    def test_historical_code_and_worktree_changes_cannot_affect_package(self):
        (self.source / 'README.md').write_text('Current checkout differs\n')
        result = self.build()
        self.assertEqual(0, result.returncode, result.stderr.decode())
        self.assertEqual(self.files, self.read_package())
        self.assertFalse((self.source / 'executed-release-code').exists())
        self.assertNotIn('Build/Scripts/build-extension.py', self.read_package())
        self.assertNotIn('.gitattributes', self.read_package())

    def test_selected_symlink_is_rejected_before_reading_target(self):
        target = self.source / 'composer.json'
        target.unlink()
        target.symlink_to(self.directory / 'nonexistent-external-json')
        revision = self.commit()
        result = self.build(revision)
        self.assertNotEqual(0, result.returncode)
        self.assertIn(b'Non-regular Git source: composer.json', result.stderr)
        self.assertFalse(self.output.exists())

    def test_required_directory_symlink_is_rejected(self):
        shutil.rmtree(self.source / 'Classes')
        (self.source / 'Classes').symlink_to(self.directory)
        revision = self.commit()
        result = self.build(revision)
        self.assertNotEqual(0, result.returncode)
        self.assertIn(b'Non-regular Git source: Classes', result.stderr)

    def test_gitlink_in_packaged_directory_is_rejected(self):
        self.git('update-index', '--add', '--cacheinfo',
                 '160000,' + self.revision + ',Classes/foreign-submodule')
        revision = self.commit(already_staged=True)
        result = self.build(revision)
        self.assertNotEqual(0, result.returncode)
        self.assertIn(b'Non-regular Git source: Classes/foreign-submodule', result.stderr)

    def test_revision_must_be_a_full_commit_object(self):
        invalid = ['HEAD', '--all', self.revision[:12], 'f' * 39,
                   self.git('rev-parse', 'HEAD^{tree}').decode(),
                   self.git('rev-parse', 'HEAD:README.md').decode()]
        for revision in invalid:
            with self.subTest(revision=revision):
                result = self.build(revision)
                self.assertNotEqual(0, result.returncode)
                self.assertFalse(self.output.exists())

    def test_blob_replace_reference_cannot_substitute_release_data(self):
        blob = self.git('rev-parse', 'HEAD:README.md').decode()
        replacement = self.git('hash-object', '-w', '--stdin', data=b'Substituted blob\n').decode()
        self.git('replace', blob, replacement)
        result = self.build()
        self.assertEqual(0, result.returncode, result.stderr.decode())
        self.assertEqual(self.files, self.read_package())

    def test_commit_replace_reference_cannot_substitute_release_tree(self):
        (self.source / 'README.md').write_text('Substituted tree\n')
        self.git('add', 'README.md')
        replacement = self.commit(already_staged=True)
        self.git('replace', self.revision, replacement)
        result = self.build()
        self.assertEqual(0, result.returncode, result.stderr.decode())
        self.assertEqual(self.files, self.read_package())


if __name__ == '__main__':
    unittest.main()
