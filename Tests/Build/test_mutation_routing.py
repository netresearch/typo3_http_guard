# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Offline Git/argv controls; the recording tool does not measure an MSI."""
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[2]
WORKFLOW = ROOT / '.github/workflows/verification.yml'
PHP_SNAPSHOT = ROOT / 'Classes/HttpGuard/PolicyException.php'
STEP_NAME = 'Enforce native mutation thresholds for the changed source or full qualification'


def mutation_step():
    # Execute the actual literal shell block. Full YAML syntax is checked by
    # actionlint separately; refuse ambiguity or a non-literal block here.
    lines = WORKFLOW.read_text(encoding='utf-8').splitlines(keepends=True)
    starts = [index for index, line in enumerate(lines)
              if line.strip() == f'- name: {STEP_NAME}']
    if len(starts) != 1:
        raise ValueError('Expected one native mutation workflow step')
    start = starts[0]
    step_indent = len(lines[start]) - len(lines[start].lstrip())
    for index in range(start + 1, len(lines)):
        line = lines[index]
        indent = len(line) - len(line.lstrip())
        if line.strip() and indent <= step_indent:
            break
        if line.strip() == 'run: |':
            content_indent = indent + 2
            content = []
            for child in lines[index + 1:]:
                if child.strip() and len(child) - len(child.lstrip()) < content_indent:
                    break
                content.append(child[content_indent:] if child.strip() else '\n')
            if not content:
                raise ValueError('Native mutation shell block is empty')
            return ''.join(content)
    raise ValueError('Expected a literal native mutation shell block')


class MutationRoutingTest(unittest.TestCase):
    def setUp(self):
        self.temporary = tempfile.TemporaryDirectory(prefix='http-guard-mutation-route-')
        self.addCleanup(self.temporary.cleanup)
        self.root = Path(self.temporary.name)
        self.step = mutation_step()
        self.env = os.environ.copy()
        for name in tuple(self.env):
            if name.startswith('GIT_') or name in ('BASH_ENV', 'ENV'):
                del self.env[name]
        self.env.update(GIT_CONFIG_NOSYSTEM='1', GIT_CONFIG_GLOBAL=os.devnull,
                        MUTATION_EVENT='pull_request', RECORDING_EXIT='0')
        self.git('init', '--quiet', '--initial-branch=fixture')
        self.git('config', 'user.name', 'Local QA fixture')
        self.git('config', 'user.email', 'qa-fixture@invalid')
        self.git('config', 'core.fileMode', 'true')
        for name in ('Classes/Direct.php', 'Classes/Nested/Contract.php', 'README.md'):
            path = self.root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            if path.suffix == '.php':
                # Immutable byte copy; this test never executes PHP source.
                shutil.copyfile(PHP_SNAPSHOT, path)
                path.chmod(0o644)
            else:
                path.write_text('fixture baseline\n')
        self.git('add', '.')
        self.git('commit', '--quiet', '-m', 'fixture baseline')
        self.base = self.git('rev-parse', 'HEAD').stdout.strip()
        self.env['MUTATION_DIFF_BASE'] = self.base
        self.trace = self.root / 'infection.trace'
        self.env['RECORDING_TRACE'] = str(self.trace)
        self.env['TMPDIR'] = str(self.root / 'owned-temporary')
        Path(self.env['TMPDIR']).mkdir()
        runner = self.root / '.Build/vendor/bin/infection'
        runner.parent.mkdir(parents=True)
        runner.write_text("""#!/usr/bin/env bash
set -euo pipefail
printf '%s\\0' "$@" > "$RECORDING_TRACE"
exit "$RECORDING_EXIT"
""")
        runner.chmod(0o755)

    def git(self, *args):
        return subprocess.run(['git', *args], cwd=self.root, env=self.env,
                              capture_output=True, text=True, check=True, timeout=10)

    def change(self, name):
        self.edit_file(name)
        self.git('add', name)
        self.git('commit', '--quiet', '-m', 'fixture change')

    def edit_file(self, name):
        path = self.root / name
        if path.suffix == '.php':
            # Mode-only Git modifications exercise AMR selection without
            # rewriting or executing any copied PHP source.
            path.chmod(0o755)
        else:
            with path.open('a') as output:
                output.write('changed fixture\n')

    def run_step(self):
        result = subprocess.run(['bash', '-c', self.step], cwd=self.root, env=self.env,
                                capture_output=True, text=True, check=False, timeout=10)
        self.assertEqual([], list(Path(self.env['TMPDIR']).iterdir()),
                         'The owned changed-file list must be removed on every exit')
        return result

    def arguments(self):
        values = self.trace.read_bytes().split(b'\0')
        self.assertEqual(b'', values.pop(), 'Recorder output must be NUL terminated')
        return [value.decode() for value in values]

    def assert_common_scope(self):
        args = self.arguments()
        for flag in ('--with-uncovered', '--with-timeouts', '--only-covering-test-cases',
                     '--threads=1'):
            self.assertIn(flag, args)
        self.assertEqual(['--configuration', 'infection.native.json5'], args[:2])
        self.assertFalse(any(value.startswith(('--min-msi', '--min-covered-msi', '--ignore',
                                               '--exclude', '--filter=', '--git-diff')) for value in args))
        self.assertEqual(1, args.count('--'))

    def selected_paths(self):
        args = self.arguments()
        return args[args.index('--') + 1:]

    def test_invalid_comparison_rejected_without_measurement(self):
        self.env['MUTATION_DIFF_BASE'] = 'invalid-base'
        result = self.run_step()
        self.assertEqual(2, result.returncode, result.stderr)
        self.assertFalse(self.trace.exists())

    def test_missing_comparison_object_rejected_without_measurement(self):
        self.env['MUTATION_DIFF_BASE'] = 'e' * 40
        result = self.run_step()
        self.assertNotEqual(0, result.returncode)
        self.assertFalse(self.trace.exists())

    def test_documentation_only_skips_without_score_claim(self):
        self.change('README.md')
        result = self.run_step()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertIn('skipped', result.stdout)
        self.assertNotIn('90', result.stdout)
        self.assertFalse(self.trace.exists())

    def test_direct_production_php_is_measured(self):
        self.change('Classes/Direct.php')
        result = self.run_step()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertTrue(self.trace.exists(), result.stdout)
        self.assert_common_scope()
        self.assertEqual(['Classes/Direct.php'], self.selected_paths())

    def test_nested_production_php_is_measured(self):
        self.change('Classes/Nested/Contract.php')
        result = self.run_step()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertTrue(self.trace.exists(), result.stdout)
        self.assert_common_scope()
        self.assertEqual(['Classes/Nested/Contract.php'], self.selected_paths())

    def test_deleted_only_source_skips_without_score_claim(self):
        self.git('rm', 'Classes/Direct.php')
        self.git('commit', '--quiet', '-m', 'fixture deletion')
        result = self.run_step()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertIn('skipped', result.stdout)
        self.assertNotIn('90', result.stdout)
        self.assertFalse(self.trace.exists())

    def test_non_pr_events_preserve_full_scope(self):
        for event in ('push', 'schedule', 'workflow_dispatch'):
            with self.subTest(event=event):
                self.env['MUTATION_EVENT'] = event
                self.env['MUTATION_DIFF_BASE'] = 'not-used'
                result = self.run_step()
                self.assertEqual(0, result.returncode, result.stderr)
                self.assert_common_scope()
                self.assertEqual([], self.selected_paths())

    def test_renamed_and_modified_production_php_is_measured(self):
        self.git('mv', 'Classes/Nested/Contract.php', 'Classes/Renamed.php')
        self.edit_file('Classes/Renamed.php')
        self.git('add', 'Classes')
        self.git('commit', '--quiet', '-m', 'fixture rename and edit')
        status = self.git('diff', '--name-status', self.base, 'HEAD').stdout
        self.assertTrue(status.startswith('R'), status)
        result = self.run_step()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertTrue(self.trace.exists(), result.stdout)
        self.assert_common_scope()
        self.assertEqual(['Classes/Renamed.php'], self.selected_paths())

    def test_literal_unusual_names_remain_separate_argv_values(self):
        names = ['Classes/Space name.php', 'Classes/Comma,name.php',
                 'Classes/Literal$(touch SHOULD_NOT_EXIST).php',
                 'Classes/-Leading.php', 'Classes/Nested/Line\nbreak.php']
        for name in names:
            path = self.root / name
            path.parent.mkdir(parents=True, exist_ok=True)
            shutil.copyfile(PHP_SNAPSHOT, path)
        self.git('add', 'Classes')
        self.git('commit', '--quiet', '-m', 'literal path fixtures')
        result = self.run_step()
        self.assertEqual(0, result.returncode, result.stderr)
        self.assert_common_scope()
        self.assertCountEqual(names, self.selected_paths())
        self.assertFalse((self.root / 'SHOULD_NOT_EXIST').exists())

    def test_symlink_source_is_rejected_without_measurement(self):
        (self.root / 'Classes/Linked.php').symlink_to('Direct.php')
        self.git('add', 'Classes')
        self.git('commit', '--quiet', '-m', 'symlink fixture')
        result = self.run_step()
        self.assertEqual(2, result.returncode, result.stderr)
        self.assertFalse(self.trace.exists())

    def test_git_diff_failure_is_propagated_without_measurement(self):
        self.change('Classes/Direct.php')
        actual_git = shutil.which('git')
        self.assertIsNotNone(actual_git)
        tools = self.root / 'recording-tools'
        tools.mkdir()
        stub = tools / 'git'
        stub.write_text('#!/usr/bin/env bash\nset -euo pipefail\n'
                        'if [[ "$1" == diff ]]; then exit 76; fi\n'
                        'exec "$ACTUAL_GIT" "$@"\n')
        stub.chmod(0o755)
        self.env['ACTUAL_GIT'] = actual_git
        self.env['PATH'] = str(tools) + os.pathsep + self.env['PATH']
        result = self.run_step()
        self.assertEqual(76, result.returncode, result.stderr)
        self.assertFalse(self.trace.exists())

    def test_recorded_measurement_failure_cannot_turn_green(self):
        self.change('Classes/Nested/Contract.php')
        self.env['RECORDING_EXIT'] = '79'
        result = self.run_step()
        self.assertEqual(79, result.returncode, result.stderr)

if __name__ == '__main__':
    unittest.main()
