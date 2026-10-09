# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Execute local guards against disposable indexes and deliberate broken wiring."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

SOURCE = Path(__file__).resolve().parents[2]


class StagedSecretsTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix='http-guard-index-')
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        scripts = self.root / 'Build/Scripts'
        scripts.mkdir(parents=True)
        for name in ('check-staged-secrets.py', 'detect-staged-secret.php'):
            shutil.copyfile(SOURCE / 'Build/Scripts' / name, scripts / name)
        (self.root / '.Build').mkdir()
        (self.root / '.Build/vendor').symlink_to((SOURCE / '.Build/vendor').resolve(), target_is_directory=True)
        self.git('init', '-q')
        self.git('config', 'user.name', 'Synthetic Guard Test')
        self.git('config', 'user.email', 'guard-test@example.invalid')
        self.git('config', 'commit.gpgsign', 'false')
        self.write('baseline.txt', 'ordinary baseline')
        self.git('add', 'baseline.txt')
        self.git('commit', '-qm', 'test: synthetic index baseline')
        self.canary = 'gh' + 'p_' + 'aB3dE6fG9hJ2kL5mN8pQ1rS4tV7wX0yZ3aB6'

    def git(self, *args, input=None):
        result = subprocess.run(['git', '--literal-pathspecs', *args], cwd=self.root,
                                input=input, capture_output=True, check=False)
        self.assertEqual(0, result.returncode, result.stderr.decode(errors='replace'))
        return result.stdout

    def write(self, path, content):
        target = self.root / path
        target.parent.mkdir(parents=True, exist_ok=True)
        target.write_text(content)

    def scan(self, expected):
        result = subprocess.run(['python3', 'Build/Scripts/check-staged-secrets.py'],
                                cwd=self.root, capture_output=True, check=False)
        self.assertEqual(expected, result.returncode, result.stderr.decode(errors='replace'))
        self.assertNotIn(self.canary.encode(), result.stdout + result.stderr)
        return result

    def test_empty_index_checks_available_detector(self):
        self.scan(0)

    def test_ordinary_staged_blob(self):
        self.write('config.json', '{"name":"ordinary documented configuration"}')
        self.git('add', '--', 'config.json')
        self.scan(0)

    def test_detector_source_can_be_staged(self):
        self.git('add', '--', 'Build/Scripts/check-staged-secrets.py', 'Build/Scripts/detect-staged-secret.php')
        self.scan(0)

    def test_aws_secret_assignment_blocked(self):
        synthetic_value = 'Aa1bBb2cCc3dDd4eEe5fFf6gGg7hHh8iIi9jJj0k'
        self.write('credential.txt', 'aws_' + 'secret_access_key = "' + synthetic_value + '"')
        self.git('add', '--', 'credential.txt')
        result = self.scan(1)
        self.assertNotIn(synthetic_value.encode(), result.stdout + result.stderr)

    def test_staged_token_blocked_after_worktree_cleaned(self):
        self.write('credential.txt', self.canary)
        self.git('add', '--', 'credential.txt')
        self.write('credential.txt', '[REDACTED]')
        self.scan(1)

    def test_unstaged_token_not_read(self):
        self.write('credential.txt', '[REDACTED]')
        self.git('add', '--', 'credential.txt')
        self.write('credential.txt', self.canary)
        self.scan(0)

    def test_newline_filename(self):
        self.write('two\nlines.txt', self.canary)
        self.git('add', '--', 'two\nlines.txt')
        self.scan(1)

    def test_tab_filename(self):
        self.write('two\tcolumns.txt', self.canary)
        self.git('add', '--', 'two\tcolumns.txt')
        self.scan(1)

    def test_literal_glob_filename(self):
        self.write('*.txt', self.canary)
        self.write('other.txt', 'ordinary')
        self.git('add', '--', '*.txt', 'other.txt')
        self.scan(1)

    def test_dash_filename(self):
        self.write('--option.txt', self.canary)
        self.git('add', '--', '--option.txt')
        self.scan(1)

    def test_non_utf8_filename(self):
        path = b'invalid-\xff.txt'
        descriptor = self.root.as_posix().encode() + b'/' + path
        with open(descriptor, 'wb') as output:
            output.write(self.canary.encode())
        self.git('add', '--', path)
        self.scan(1)

    def record_secret_baseline(self):
        self.write('old.txt', self.canary)
        self.git('add', '--', 'old.txt')
        self.git('commit', '-qm', 'test: synthetic pre-existing token')

    def test_renamed_blob_scanned(self):
        self.record_secret_baseline()
        self.git('mv', '--', 'old.txt', 'new.txt')
        self.scan(1)

    def test_deleted_blob_not_reintroduced(self):
        self.record_secret_baseline()
        self.git('rm', '--', 'old.txt')
        self.scan(0)

    def test_blob_replace_ref_cannot_mask_staged_secret(self):
        self.write('credential.txt', self.canary)
        self.git('add', '--', 'credential.txt')
        secret_object = self.git('rev-parse', ':credential.txt').strip().decode()
        safe_object = self.git('hash-object', '-w', '--stdin', input=b'ordinary').strip().decode()
        self.git('replace', secret_object, safe_object)
        self.scan(1)

    def test_symlink_target_blob_scanned_without_dereference(self):
        (self.root / 'link').symlink_to(self.canary)
        self.git('add', '--', 'link')
        self.scan(1)

    def test_private_key_header_blocked(self):
        self.write('private.pem', '-----BEGIN ' + 'RSA PRIVATE KEY-----\nsynthetic\n')
        self.git('add', '--', 'private.pem')
        self.scan(1)

    def test_exact_public_synthetic_key_allowed(self):
        path = 'Tests/HttpGuard/Integration/certificates/ca.key'
        target = self.root / path
        target.parent.mkdir(parents=True)
        shutil.copyfile(SOURCE / path, target)
        self.git('add', '--', path)
        self.scan(0)

    def test_changed_synthetic_key_blocked(self):
        path = 'Tests/HttpGuard/Integration/certificates/ca.key'
        self.write(path, (SOURCE / path).read_text() + '\n')
        self.git('add', '--', path)
        self.scan(1)

    def test_public_key_copy_at_other_path_blocked(self):
        self.write('foreign.key', (SOURCE / 'Tests/HttpGuard/Integration/certificates/ca.key').read_text())
        self.git('add', '--', 'foreign.key')
        self.scan(1)

    def test_unmerged_index_rejected(self):
        object_id = self.git('hash-object', '-w', '--stdin', input=b'ordinary').strip()
        records = b''.join(b'100644 ' + object_id + b' ' + stage + b'\tconflict.txt\n'
                           for stage in (b'1', b'2', b'3'))
        self.git('update-index', '--index-info', input=records)
        self.scan(2)

    def test_missing_detector_never_silently_skipped(self):
        (self.root / '.Build/vendor').unlink()
        self.scan(2)

    def test_shell_metacharacters_do_not_execute(self):
        self.write('payload.txt', '$(touch DO_NOT_CREATE); harmless text')
        self.git('add', '--', 'payload.txt')
        self.scan(0)
        self.assertFalse((self.root / 'DO_NOT_CREATE').exists())


class HarnessBindingTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix='http-guard-harness-')
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        scripts = self.root / 'Build/Scripts'
        scripts.mkdir(parents=True)
        shutil.copyfile(SOURCE / 'Build/Scripts/verify-harness.py', scripts / 'verify-harness.py')
        for name in ('AGENTS.md', '.github/copilot-instructions.md', '.github/instructions/php.instructions.md',
                     '.github/instructions/tests.instructions.md', '.github/CODEOWNERS', 'captainhook.json'):
            self.copy(name)
        # Copy only documented entry points, never vendor or historical evidence.
        import re
        for document in (self.root / 'AGENTS.md', self.root / '.github/copilot-instructions.md'):
            for link in re.findall(r'\]\(([^)]+)\)', document.read_text()):
                if link.startswith(('https://', 'http://', '#')):
                    continue
                target = (document.parent / link).relative_to(self.root)
                original = SOURCE / target
                if original.is_dir():
                    (self.root / target).mkdir(parents=True, exist_ok=True)
                else:
                    self.copy(target)
        manifest = json.loads((SOURCE / 'composer.json').read_text())
        manifest['scripts'] = {name: 'true' for name in (
            'ci:test:php:cgl', 'ci:test:php:phpstan', 'ci:test:php:unit', 'ci:test:php:functional')}
        manifest['scripts'].update({
            'check:harness': 'python3 Build/Scripts/verify-harness.py',
            'check:secrets': 'python3 Build/Scripts/check-staged-secrets.py',
            'check:local': 'bash Build/Scripts/check-local-quality.sh'})
        self.manifest = manifest
        self.save_manifest()
        self.workflow = self.root / '.github/workflows/ci.yml'
        self.workflow.parent.mkdir(parents=True)
        self.workflow.write_text('jobs:\n  quality:\n    steps:\n      - run: |\n          composer check:harness\n')

    def copy(self, name):
        target = self.root / name
        target.parent.mkdir(parents=True, exist_ok=True)
        shutil.copyfile(SOURCE / name, target)

    def save_manifest(self):
        (self.root / 'composer.json').write_text(json.dumps(self.manifest))

    def scan(self, expected):
        result = subprocess.run(['python3', 'Build/Scripts/verify-harness.py'], cwd=self.root,
                                capture_output=True, check=False)
        self.assertEqual(expected, result.returncode, result.stderr.decode(errors='replace'))

    def test_real_run_and_valid_pointers(self):
        self.scan(0)

    def test_comment_does_not_count_as_ci_execution(self):
        self.workflow.write_text('# composer check:harness\njobs: {}\n')
        self.scan(1)

    def test_composer_command_drift_rejected(self):
        self.manifest['scripts']['check:local'] = 'true'
        self.save_manifest()
        self.scan(1)

    def test_disabled_installer_rejected(self):
        self.manifest['config']['allow-plugins']['captainhook/hook-installer'] = False
        self.save_manifest()
        self.scan(1)

    def test_broken_pointer_rejected(self):
        (self.root / 'Configuration/Services.yaml').unlink()
        self.scan(1)

    def test_overlong_agent_index_rejected(self):
        with (self.root / 'AGENTS.md').open('a') as output:
            output.write('\n' * 150)
        self.scan(1)

    def test_missing_routing_owner_rejected(self):
        (self.root / '.github/CODEOWNERS').write_text('# no owner\n')
        self.scan(1)

    def test_wrong_captainhook_bootstrap_rejected(self):
        path = self.root / 'captainhook.json'
        hooks = json.loads(path.read_text())
        hooks['config']['bootstrap'] = 'vendor/autoload.php'
        path.write_text(json.dumps(hooks))
        self.scan(1)


if __name__ == '__main__':
    unittest.main()
