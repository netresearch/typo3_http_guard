# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Fixture-check failures reveal fixed error codes without leaking input bytes."""
import errno
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

SOURCE = Path(__file__).resolve().parents[2]
SCRIPT = SOURCE / 'Build/Scripts/check-synthetic-fixtures.py'
CANARY = 'fixture-diagnostic-private-message-and-path'


class FixtureDiagnosticsTest(unittest.TestCase):
    def invoke(self, failure):
        program = '''
import importlib.util
import sys
spec = importlib.util.spec_from_file_location('fixture_guard', sys.argv[1])
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
def fail(_root):
    raise FAILURE
module.check = fail
sys.argv = [sys.argv[1]]
raise SystemExit(module.main())
'''.replace('FAILURE', failure)
        result = subprocess.run(['python3', '-c', program, str(SCRIPT)],
                                capture_output=True, check=False, timeout=10)
        self.assertEqual(1, result.returncode)
        self.assertEqual(b'', result.stdout)
        self.assertNotIn(CANARY.encode(), result.stderr)
        return result.stderr.decode()

    def test_filesystem_error_has_fixed_code_without_message_or_path(self):
        message = self.invoke(f'OSError({errno.EIO}, {CANARY!r}, {CANARY!r})')
        self.assertEqual('FAIL: synthetic fixture integrity check (filesystem EIO)\n', message)

    def test_unknown_errno_has_fixed_diagnostic(self):
        message = self.invoke(f'OSError(-1, {CANARY!r}, {CANARY!r})')
        self.assertEqual('FAIL: synthetic fixture integrity check (filesystem unknown)\n', message)

    def test_string_errno_cannot_become_a_diagnostic(self):
        message = self.invoke(f'OSError({CANARY!r}, {CANARY!r}, {CANARY!r})')
        self.assertEqual('FAIL: synthetic fixture integrity check (filesystem unknown)\n', message)

    def test_fixture_failure_retains_generic_diagnostic(self):
        message = self.invoke(f'module.FixtureError({CANARY!r})')
        self.assertEqual('FAIL: synthetic fixture integrity check\n', message)

    def test_unicode_failure_retains_generic_diagnostic(self):
        message = self.invoke(f'UnicodeError({CANARY!r})')
        self.assertEqual('FAIL: synthetic fixture integrity check\n', message)

    def test_failed_source_inventory_iteration_cannot_pass(self):
        # Simulate a source inventory failing mid-iteration without relying on
        # interpreter-specific pathlib implementation details or live races.
        program = '''
import importlib.util
from pathlib import Path
import sys
spec = importlib.util.spec_from_file_location('fixture_guard', sys.argv[1])
module = importlib.util.module_from_spec(spec)
spec.loader.exec_module(module)
root = Path(sys.argv[2])
(root / module.FIXTURE_DIRECTORY).mkdir(parents=True)
original_rglob = Path.rglob
def failed_inventory(self, pattern):
    if self == root:
        yield root / 'ordinary.txt'
        raise OSError(5, 'fixture-diagnostic-private-message-and-path',
                      'fixture-diagnostic-private-message-and-path')
    yield from original_rglob(self, pattern)
Path.rglob = failed_inventory
sys.argv = [sys.argv[1], '--root', str(root)]
raise SystemExit(module.main())
'''
        with tempfile.TemporaryDirectory(prefix='http-guard-fixture-errors-') as temporary:
            result = subprocess.run(['python3', '-c', program, str(SCRIPT), temporary],
                                    capture_output=True, check=False, timeout=10)
        self.assertEqual(1, result.returncode)
        self.assertEqual(b'', result.stdout)
        self.assertNotIn(CANARY.encode(), result.stderr)
        self.assertEqual(b'FAIL: synthetic fixture integrity check (filesystem EIO)\n', result.stderr)


class FixtureIntegrityTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix='http-guard-tls-integrity-')
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.relative = Path('Tests/HttpGuard/Integration/certificates')
        self.directory = self.root / self.relative
        self.directory.mkdir(parents=True)
        for identity in ('ca', 'client', 'server'):
            for extension in ('key', 'crt'):
                name = identity + '.' + extension
                shutil.copyfile(SOURCE / self.relative / name, self.directory / name)

    def run_guard(self):
        return subprocess.run(['python3', str(SCRIPT), '--root', str(self.root)],
                              capture_output=True, check=False, timeout=10)

    def assert_denied(self):
        result = self.run_guard()
        self.assertEqual(1, result.returncode)
        self.assertEqual(b'', result.stdout)
        self.assertEqual(b'FAIL: synthetic fixture integrity check\n', result.stderr)

    def test_exact_public_fixture_chain_passes(self):
        result = self.run_guard()
        self.assertEqual(0, result.returncode, result.stderr.decode())
        self.assertEqual(b'', result.stderr)
        self.assertIn(b'"certificate_chain_verified": true', result.stdout)

    def test_changed_fixture_bytes_are_denied(self):
        with (self.directory / 'ca.key').open('ab') as output:
            output.write(b'\n')
        self.assert_denied()

    def test_additional_private_key_path_is_denied(self):
        (self.directory / 'additional.pem').write_text('public synthetic test canary')
        self.assert_denied()

    def test_additional_scanner_excluded_suffix_is_denied(self):
        path = self.root / 'additional' / self.relative / 'ca.key'
        path.parent.mkdir(parents=True)
        path.write_text('public synthetic test canary')
        self.assert_denied()


if __name__ == '__main__':
    unittest.main()
