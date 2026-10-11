# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Offline CLI routing controls; these do not qualify any Core runtime."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

SOURCE = Path(__file__).resolve().parents[2]


class FunctionalRoutingTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix='http-guard-functional-')
        self.addCleanup(self.temporary.cleanup)
        self.base = Path(self.temporary.name)
        self.root = self.base / 'project'
        scripts = self.root / 'Build/Scripts'
        scripts.mkdir(parents=True)
        shutil.copyfile(SOURCE / 'Build/Scripts/runTests.sh', scripts / 'runTests.sh')
        shutil.copyfile(SOURCE / 'Build/Scripts/runTests.conf', scripts / 'runTests.conf')
        entries = self.root / 'Tests/Functional'
        entries.mkdir(parents=True)
        for entry in ('production', 'classic-package', 'mode'):
            shutil.copyfile(SOURCE / f'Tests/Functional/{entry}-bootstrap.php',
                            entries / f'{entry}-bootstrap.php')
        self.fixture = self.root / 'fixtures/core'
        (self.fixture / 'vendor').mkdir(parents=True)
        # Deliberately incomplete input: no Core classes or fabricated ABI.
        (self.fixture / 'vendor/autoload.php').touch()
        self.env = os.environ.copy()
        for key in ('HTTP_GUARD_FIXTURE', 'HTTP_GUARD_EXPECTED_CORE', 'BASH_ENV'):
            self.env.pop(key, None)
        self.trace = self.base / 'route.trace'
        self.env['ROUTE_TRACE'] = str(self.trace)
        self.root_trace = self.base / 'root.trace'
        self.env['ROUTE_ROOT_TRACE'] = str(self.root_trace)
        self.container_trace = self.base / 'container.trace'
        self.cleanup_trace = self.base / 'cleanup.trace'
        self.env['ROUTE_CONTAINER_TRACE'] = str(self.container_trace)
        self.env['ROUTE_CLEANUP_TRACE'] = str(self.cleanup_trace)

    def run_route(self, *arguments):
        return subprocess.run(['bash', 'Build/Scripts/runTests.sh', *arguments],
                              cwd=self.root, env=self.env, capture_output=True,
                              text=True, check=False)

    def shared_routing_double(self):
        # Exercise the real project hook; replace only container I/O. These
        # controls prove routing and ownership, never Core ABI or wire behavior.
        docker = self.base / 'docker-double'
        docker.write_text(r'''#!/usr/bin/env python3
import os
from pathlib import Path
import sys

args = sys.argv[1:]
if args[0] == 'create':
    with open(os.environ['ROUTE_CONTAINER_TRACE'], 'ab') as trace:
        trace.write(b'CALL\0' + b''.join(value.encode() + b'\0' for value in args))
    cid_file = Path(args[args.index('--cidfile') + 1])
    probe = cid_file.name == 'probe.cid'
    image = next(index for index, value in enumerate(args) if '@sha256:' in value)
    with open(os.environ['ROUTE_TRACE'], 'ab') as trace:
        trace.write(b'CALL\0' + b''.join(value.encode() + b'\0' for value in args[image + 1:]))
    cid_file.write_text(('a' if probe else 'b') * 64)
    sys.exit(int(os.environ.get('ROUTE_CREATE_EXIT', '0')))
if args[0] == 'start':
    sys.exit(int(os.environ.get('ROUTE_PROBE_EXIT', '0')) if args[-1] == 'a' * 64
             else int(os.environ.get('ROUTE_ENTRY_EXIT', '71')))
if args[0] == 'rm':
    with open(os.environ['ROUTE_CLEANUP_TRACE'], 'ab') as trace:
        trace.write(args[-1].encode() + b'\0')
    sys.exit(int(os.environ.get('ROUTE_CLEANUP_EXIT', '0')))
sys.exit(86)
''')
        docker.chmod(0o755)
        self.env['ROUTE_DOCKER'] = str(docker)
        runner = self.root / '.Build/vendor/netresearch/typo3-ci-workflows/assets/Build/Scripts/runTests.sh'
        runner.parent.mkdir(parents=True)
        runner.write_text('''#!/usr/bin/env bash
set -euo pipefail
printf '%s\\0' "${RUNTESTS_PROJECT_ROOT:-}" >> "$ROUTE_ROOT_TRACE"
while [[ $# -gt 0 && "$1" != -- ]]; do
    case "$1" in
        -s) [[ "$2" == http_guard_functional ]] || exit 84; shift 2 ;;
        -p|-b) shift 2 ;;
        *) exit 85 ;;
    esac
done
shift
PROJECT_ROOT=$PWD
CONTAINER_BIN=$ROUTE_DOCKER
IMAGE_PHP=${ROUTE_IMAGE:-fixture-php@sha256:0000000000000000000000000000000000000000000000000000000000000000}
source Build/Scripts/runTests.conf
suite_http_guard_functional "$@"
''')
        runner.chmod(0o755)

    def calls(self):
        return [[part.decode() for part in call.split(b'\0') if part]
                for call in self.trace.read_bytes().split(b'CALL\0') if call]

    def realpath_without_gnu_options(self):
        """Model the pinned image's argument contract, not a Core runtime."""
        canonicalizer = shutil.which('realpath')
        self.assertIsNotNone(canonicalizer)
        commands = self.base / 'portable-commands'
        commands.mkdir()
        command = commands / 'realpath'
        command.write_text('''#!/usr/bin/env bash
set -euo pipefail
for argument in "$@"; do
    [[ "$argument" != -* ]] || exit 87
done
exec "$PORTABLE_REALPATH" "$@"
''')
        command.chmod(0o755)
        self.env['PORTABLE_REALPATH'] = canonicalizer
        self.env['PATH'] = str(commands) + os.pathsep + self.env['PATH']

    def test_selected_container_path_resolution_does_not_need_gnu_options(self):
        self.realpath_without_gnu_options()
        self.shared_routing_double()
        result = self.run_route('-s', 'classic', '-p', '8.5', '-t', '14',
                                '-f', str(self.fixture), '--', 'active')
        self.assertEqual(71, result.returncode, result.stderr)
        self.assertEqual(2, len(self.calls()))
        self.assertEqual(str(self.fixture), self.calls()[-1][-3])

    def test_portable_canonicalization_keeps_loader_escape_rejection(self):
        self.realpath_without_gnu_options()
        shutil.rmtree(self.fixture / 'vendor')
        external = self.base / 'portable-outside-vendor'
        external.mkdir()
        (external / 'autoload.php').touch()
        (self.fixture / 'vendor').symlink_to(external, target_is_directory=True)
        self.shared_routing_double()
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(2, result.returncode, result.stderr)
        self.assertIn('inside the project mount', result.stderr)
        self.assertFalse(self.trace.exists())

    def test_missing_fixture_is_explicit_cli_error(self):
        result = self.run_route('-s', 'integration')
        self.assertEqual(2, result.returncode)
        self.assertIn('prepared genuine Core fixture', result.stderr)

    def test_nonexistent_fixture_is_rejected(self):
        result = self.run_route('-s', 'integration', '-f', 'missing')
        self.assertEqual(2, result.returncode)
        self.assertIn('Fixture directory', result.stderr)

    def test_line_break_in_fixture_path_is_rejected_without_canonicalizing_to_another_input(self):
        fixture = self.root / 'fixtures/core\n'
        (fixture / 'vendor').mkdir(parents=True)
        (fixture / 'vendor/autoload.php').touch()
        result = self.run_route('-s', 'integration', '-f', str(fixture))
        self.assertEqual(2, result.returncode)
        self.assertIn('line breaks', result.stderr)

    def test_unsupported_shared_mount_root_rejected_before_delegation(self):
        renamed = self.base / 'project space'
        self.root.rename(renamed)
        self.root = renamed
        self.fixture = self.root / 'fixtures/core'
        self.shared_routing_double()
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(2, result.returncode)
        self.assertIn('checkout path', result.stderr)
        self.assertFalse(self.trace.exists())

    def test_missing_loader_is_rejected(self):
        (self.fixture / 'vendor/autoload.php').unlink()
        result = self.run_route('-s', 'integration', '-f', str(self.fixture))
        self.assertEqual(2, result.returncode)
        self.assertIn('vendor/autoload.php', result.stderr)

    def test_fixture_option_rejected_for_unrelated_suite(self):
        result = self.run_route('-s', 'unit', '-f', str(self.fixture))
        self.assertEqual(2, result.returncode)

    def test_modes_and_phases_are_validated_before_core_load(self):
        for suite, arguments in (('mode', []), ('mode', ['invalid']),
                                 ('classic', ['invalid']), ('integration', ['invalid'])):
            with self.subTest(suite=suite, arguments=arguments):
                result = self.run_route('-s', suite, '-f', str(self.fixture), '--', *arguments)
                self.assertEqual(2, result.returncode)

    def test_external_fixture_rejected_before_container_delegation(self):
        external = self.base / 'outside'
        (external / 'vendor').mkdir(parents=True)
        (external / 'vendor/autoload.php').touch()
        self.shared_routing_double()
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(external))
        self.assertEqual(2, result.returncode)
        self.assertIn('inside the project mount', result.stderr)
        self.assertFalse(self.trace.exists())

    def test_symlink_to_external_fixture_rejected_before_delegation(self):
        external = self.base / 'outside'
        (external / 'vendor').mkdir(parents=True)
        (external / 'vendor/autoload.php').touch()
        (self.root / 'fixture-link').symlink_to(external, target_is_directory=True)
        self.shared_routing_double()
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', 'fixture-link')
        self.assertEqual(2, result.returncode)
        self.assertFalse(self.trace.exists())

    def test_sibling_prefix_is_outside_project_mount(self):
        sibling = self.base / 'project-sibling'
        (sibling / 'vendor').mkdir(parents=True)
        (sibling / 'vendor/autoload.php').touch()
        self.shared_routing_double()
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(sibling))
        self.assertEqual(2, result.returncode)
        self.assertFalse(self.trace.exists())

    def test_external_vendor_symlink_rejected_before_delegation(self):
        shutil.rmtree(self.fixture / 'vendor')
        external = self.base / 'outside-vendor'
        external.mkdir()
        (external / 'autoload.php').touch()
        (self.fixture / 'vendor').symlink_to(external, target_is_directory=True)
        self.shared_routing_double()
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(2, result.returncode)
        self.assertFalse(self.trace.exists())

    def test_container_receives_fixture_argv_and_probes_its_core(self):
        self.shared_routing_double()
        result = self.run_route('-s', 'integration', '-p', '8.5', '-t', '14',
                                '-f', str(self.fixture), '--', 'classic')
        self.assertEqual(71, result.returncode)
        self.assertEqual(2, len(self.calls()))
        probe, route = self.calls()
        self.assertEqual(['php', 'Build/Scripts/assert-test-runtime.php',
                          str(self.fixture / 'vendor/autoload.php'), '8.5', '^14.3.8'], probe[-5:])
        self.assertEqual(['bash', 'Build/Scripts/runTests.sh', '-s', 'integration',
                          '-f', str(self.fixture), '--', 'classic'], route[-8:])
        self.assertNotIn('require', probe)
        self.assertNotIn('update', probe)

    def test_environment_fixture_becomes_explicit_container_argv(self):
        self.env['HTTP_GUARD_FIXTURE'] = str(self.fixture)
        self.shared_routing_double()
        result = self.run_route('-s', 'classic', '-p', '8.5', '--', 'active')
        self.assertEqual(71, result.returncode)
        self.assertEqual(['bash', 'Build/Scripts/runTests.sh', '-s', 'classic',
                          '-f', str(self.fixture), '--', 'active'], self.calls()[-1][-8:])

    def test_runtime_probe_failure_prevents_entry_execution(self):
        self.shared_routing_double()
        self.env['ROUTE_PROBE_EXIT'] = '73'
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(73, result.returncode)
        self.assertEqual(1, len(self.calls()))
        self.assertEqual(('a' * 64).encode() + b'\0', self.cleanup_trace.read_bytes())

    def test_hook_creates_only_bounded_pinned_unprivileged_containers(self):
        self.shared_routing_double()
        result = self.run_route('-s', 'mode', '-p', '8.5', '-f', str(self.fixture), '--', 'observe')
        self.assertEqual(71, result.returncode, result.stderr)
        creates = [[part.decode() for part in call.split(b'\0') if part]
                   for call in self.container_trace.read_bytes().split(b'CALL\0') if call]
        self.assertEqual(2, len(creates))
        for create in creates:
            self.assertEqual('host', create[create.index('--network') + 1])
            self.assertIn('--read-only', create)
            self.assertEqual('ALL', create[create.index('--cap-drop') + 1])
            self.assertEqual('no-new-privileges', create[create.index('--security-opt') + 1])
            self.assertNotIn('--privileged', create)
            self.assertEqual(f'{self.root}:{self.root}', create[create.index('--volume') + 1])
            self.assertEqual(str(self.root), create[create.index('--workdir') + 1])
            self.assertTrue(any(value.endswith('@sha256:' + '0' * 64) for value in create))
        self.assertEqual((('a' * 64) + '\0' + ('b' * 64) + '\0').encode(), self.cleanup_trace.read_bytes())

    def test_unpinned_hook_image_is_rejected_before_container_creation(self):
        self.shared_routing_double()
        self.env['ROUTE_IMAGE'] = 'fixture-php:latest'
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(2, result.returncode)
        self.assertFalse(self.container_trace.exists())

    def test_create_failure_cleans_only_its_written_owned_identity(self):
        self.shared_routing_double()
        self.env['ROUTE_CREATE_EXIT'] = '74'
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(74, result.returncode)
        self.assertEqual(1, len(self.calls()))
        self.assertEqual(('a' * 64).encode() + b'\0', self.cleanup_trace.read_bytes())

    def test_successful_entry_cannot_mask_cleanup_failure(self):
        self.shared_routing_double()
        self.env['ROUTE_ENTRY_EXIT'] = '0'
        self.env['ROUTE_CLEANUP_EXIT'] = '75'
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(1, result.returncode)
        self.assertIn('Unable to remove an owned Functional container', result.stderr)

    def test_entry_failure_is_preserved_when_cleanup_also_fails(self):
        self.shared_routing_double()
        self.env['ROUTE_CLEANUP_EXIT'] = '75'
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(71, result.returncode)
        self.assertIn('Unable to remove an owned Functional container', result.stderr)

    def test_inherited_shared_root_override_cannot_change_checked_mount(self):
        self.shared_routing_double()
        self.env['RUNTESTS_PROJECT_ROOT'] = str(self.base / 'another-project')
        result = self.run_route('-s', 'integration', '-p', '8.5', '-f', str(self.fixture))
        self.assertEqual(71, result.returncode)
        self.assertEqual(str(self.root).encode() + b'\0', self.root_trace.read_bytes())

    def test_relative_fixture_is_resolved_from_project_root(self):
        self.shared_routing_double()
        result = subprocess.run(
            ['bash', str(self.root / 'Build/Scripts/runTests.sh'), '-s', 'integration',
             '-p', '8.5', '-f', 'fixtures/core'], cwd=self.base, env=self.env,
            capture_output=True, text=True, check=False)
        self.assertEqual(71, result.returncode)
        self.assertEqual(str(self.fixture), self.calls()[-1][-2])

    def test_host_can_use_external_fixture_without_claiming_core_success(self):
        external = self.base / 'outside'
        self.fixture.rename(external)
        self.env['HTTP_GUARD_FIXTURE'] = str(external)
        result = self.run_route('-s', 'integration')
        self.assertEqual(1, result.returncode)
        self.assertIn('SystemEnvironmentBuilder', result.stdout)
        self.assertIn(str(external), result.stdout)
        self.assertNotIn('PASS', result.stdout)

    def test_metacharacters_are_literal_fixture_argument(self):
        fixture = self.root / 'fixtures/core $(touch DO_NOT_CREATE); literal'
        self.fixture.rename(fixture)
        self.shared_routing_double()
        result = self.run_route('-s', 'mode', '-p', '8.5', '-f', str(fixture), '--', 'observe')
        self.assertEqual(71, result.returncode)
        self.assertEqual(str(fixture), self.calls()[-1][-3])
        self.assertFalse((self.root / 'DO_NOT_CREATE').exists())

    def test_installed_composer_exec_preserves_literal_argv(self):
        # Exercise actual Composer argument escaping without a container,
        # a Core mock, dependency installation or a network operation.
        (self.root / 'composer.json').write_text('{}\n')
        literal = 'fixtures/core $(touch DO_NOT_CREATE); literal'
        result = subprocess.run(
            ['composer', '--no-plugins', 'exec', '--', 'bash', '-c',
             'printf \'%s\\0\' "$@" > "$ROUTE_TRACE"', '--', literal],
            cwd=self.root, env=self.env, capture_output=True, text=True, check=False)
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual(literal.encode() + b'\0', self.trace.read_bytes())
        self.assertFalse((self.root / 'DO_NOT_CREATE').exists())

    def test_real_php_propagates_incomplete_fixture_failure_for_each_entry(self):
        for suite, arguments, entry in (('integration', [], 'production'),
                                         ('classic', ['active'], 'classic-package'),
                                         ('mode', ['observe'], 'mode')):
            with self.subTest(suite=suite):
                result = self.run_route('-s', suite, '-f', str(self.fixture), '--', *arguments)
                self.assertNotEqual(0, result.returncode)
                self.assertNotEqual(2, result.returncode)
                if suite == 'classic':
                    self.assertIn(f'Tests/Functional/{entry}-bootstrap.php', result.stderr)
                else:
                    self.assertIn('FAIL', result.stdout)
                    self.assertIn('SystemEnvironmentBuilder', result.stdout)
                self.assertNotIn('PASS', result.stdout)


if __name__ == '__main__':
    unittest.main()
