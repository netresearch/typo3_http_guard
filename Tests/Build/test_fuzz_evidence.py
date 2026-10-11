# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Fuzz evidence must fail when upstream reports success without valid execution."""
import json
from pathlib import Path
import subprocess
import tempfile
import unittest

HELPER = Path(__file__).resolve().parents[2] / 'Build/Scripts/fuzz-evidence.php'


class FuzzEvidenceTest(unittest.TestCase):
    def run_helper(self, *arguments):
        return subprocess.run(['php', str(HELPER), *map(str, arguments)], capture_output=True, text=True)

    def test_seed_boundaries_remain_exact_positive_uint32_values(self):
        with tempfile.TemporaryDirectory() as temporary:
            manifest = Path(temporary) / 'seeds.json'
            manifest.write_text(json.dumps({'seeds': [1, 4294967295]}))
            result = self.run_helper('seeds', manifest)
            self.assertEqual(0, result.returncode, result.stderr)
            self.assertEqual('1\n4294967295\n', result.stdout)

    def test_invalid_manifests_fail_without_producing_seed_values(self):
        with tempfile.TemporaryDirectory() as temporary:
            manifest = Path(temporary) / 'seeds.json'
            for seeds in ([], [1, 1], [0], [-1], [4294967296], [1.5], ['1'],
                          {'one': 1}, {'0': 1}, {'0': 1, '1': 2}):
                with self.subTest(seeds=seeds):
                    manifest.write_text(json.dumps({'seeds': seeds}))
                    result = self.run_helper('seeds', manifest)
                    self.assertNotEqual(0, result.returncode)
                    self.assertEqual('', result.stdout)

    def test_complete_count_and_bounded_upstream_overshoot_are_accepted(self):
        with tempfile.TemporaryDirectory() as temporary:
            log = Path(temporary) / 'fuzz.log'
            for calls in (10002, 10006):
                with self.subTest(calls=calls):
                    log.write_text(f'HTTP_GUARD_FUZZ_TARGET_CALLS={calls}\n')
                    result = self.run_helper('verify', temporary, 10002)
                    self.assertEqual(0, result.returncode, result.stderr)
                    self.assertEqual(f'{calls}\n', result.stdout)

    def test_crash_incomplete_and_missing_evidence_are_failures(self):
        with tempfile.TemporaryDirectory() as temporary:
            log = Path(temporary) / 'fuzz.log'
            for text in ('CORPUS CRASH harmless-fixture\nHTTP_GUARD_FUZZ_TARGET_CALLS=10002\n',
                         'CRASH harmless-fixture\nHTTP_GUARD_FUZZ_TARGET_CALLS=10002\n',
                         'HTTP_GUARD_FUZZ_TARGET_CALLS=10001\n',
                         'HTTP_GUARD_FUZZ_TARGET_CALLS=10007\n', 'No completion marker\n'):
                with self.subTest(text=text):
                    log.write_text(text)
                    self.assertNotEqual(0, self.run_helper('verify', temporary, 10002).returncode)
            log.write_text('HTTP_GUARD_FUZZ_TARGET_CALLS=10002\n')
            (Path(temporary) / 'crash-fixture.txt').write_text('harmless fixture')
            self.assertNotEqual(0, self.run_helper('verify', temporary, 10002).returncode)


if __name__ == '__main__':
    unittest.main()
