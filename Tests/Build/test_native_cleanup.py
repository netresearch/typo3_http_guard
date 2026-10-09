"""Offline native teardown controls; no Docker process or network is used."""
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH

from pathlib import Path
import subprocess
import tempfile
import unittest


ROOT = Path(__file__).resolve().parents[2]
SCRIPT = ROOT / 'Build/Scripts/run-native-tests.sh'


class NativeCleanupTest(unittest.TestCase):
    def run_cleanup(self, primary=0, failed_identity='', fail_keys=False):
        source = SCRIPT.read_text(encoding='utf-8')
        start = source.index('\ncleanup() {\n')
        end = source.index('\ntrap cleanup EXIT\n', start)
        # Exercise the actual teardown function and EXIT trap from the runner.
        fragment = source[start:end] + '\ntrap cleanup EXIT\n'
        with tempfile.TemporaryDirectory(prefix='http-guard-native-cleanup-') as temporary:
            run_dir = Path(temporary) / 'owned-run'
            certificates = run_dir / 'certificates'
            certificates.mkdir(parents=True)
            (certificates / 'synthetic-marker').write_text('fixture', encoding='utf-8')
            trace = Path(temporary) / 'calls.bin'
            setup = r'''
set -euo pipefail
run_dir=$1
trace=$2
test_exit=$3
failed_identity=$4
owned_containers=(owned-container-a owned-container-b)
owned_networks=(owned-network)
docker() {
    local identity=${@: -1}
    printf '%s\0' "$@" '' >> "$trace"
    [[ $failed_identity != all && $identity != "$failed_identity" ]]
}
'''
            if fail_keys:
                setup += '\nrm() { return 1; }\n'
            result = subprocess.run(
                ['bash', '-c', setup + fragment + '\nexit "$test_exit"\n',
                 'native-cleanup-control', str(run_dir), str(trace),
                 str(primary), failed_identity],
                capture_output=True, text=True, check=False,
            )
            records = [record.split(b'\0') for record in trace.read_bytes().split(b'\0\0') if record]
            self.assertEqual(records, [
                [b'rm', b'-f', b'owned-container-a'],
                [b'rm', b'-f', b'owned-container-b'],
                [b'network', b'rm', b'owned-network'],
            ], 'Every exact owned removal must be attempted; foreign resources must never be addressed')
            self.assertNotIn('foreign', result.stdout + result.stderr)
            self.assertEqual(certificates.exists(), fail_keys)
            return result

    def test_success_and_complete_cleanup_remain_success(self):
        self.assertEqual(self.run_cleanup().returncode, 0)

    def test_success_cannot_mask_owned_container_cleanup_failure(self):
        self.assertNotEqual(self.run_cleanup(failed_identity='owned-container-a').returncode, 0)

    def test_success_cannot_mask_owned_network_cleanup_failure(self):
        self.assertNotEqual(self.run_cleanup(failed_identity='owned-network').returncode, 0)

    def test_original_test_failure_survives_all_cleanup_failures(self):
        self.assertEqual(self.run_cleanup(primary=17, failed_identity='all').returncode, 17)

    def test_success_cannot_mask_all_owned_resource_cleanup_failures(self):
        self.assertNotEqual(self.run_cleanup(failed_identity='all').returncode, 0)

    def test_success_cannot_mask_private_fixture_cleanup_failure(self):
        self.assertNotEqual(self.run_cleanup(fail_keys=True).returncode, 0)

    def test_original_test_failure_survives_private_fixture_cleanup_failure(self):
        self.assertEqual(self.run_cleanup(primary=17, fail_keys=True).returncode, 17)


if __name__ == '__main__':
    unittest.main()
