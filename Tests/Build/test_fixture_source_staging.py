# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Fixture-digest source representation works with the unchanged staged scanner."""
import ast
import importlib.util
from pathlib import Path
import unittest


SPEC = importlib.util.spec_from_file_location('http_guard_local_guards', Path(__file__).with_name('test_local_guards.py'))
LOCAL_GUARDS = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(LOCAL_GUARDS)


class FixtureSourceStagingTest(unittest.TestCase):
    def setUp(self):
        self.guard = LOCAL_GUARDS.StagedSecretsTest()
        self.guard.setUp()
        self.addCleanup(self.guard.doCleanups)
        self.path = 'Build/Scripts/check-synthetic-fixtures.py'
        self.source = (LOCAL_GUARDS.SOURCE / self.path).read_text()
        fixture_spec = importlib.util.spec_from_file_location('http_guard_fixture_expectations', LOCAL_GUARDS.SOURCE / self.path)
        fixture_module = importlib.util.module_from_spec(fixture_spec)
        fixture_spec.loader.exec_module(fixture_module)
        self.expected = dict(fixture_module.EXPECTED)

    def test_public_fixture_validator_source_can_be_staged(self):
        self.guard.write(self.path, self.source)
        self.guard.git('add', '--', self.path)
        self.guard.scan(0)

    def test_credential_shaped_digest_assignments_remain_blocked(self):
        tree = ast.parse(self.source)
        assignment = next(
            node for node in tree.body
            if isinstance(node, ast.Assign)
            and any(isinstance(target, ast.Name) and target.id == 'EXPECTED' for target in node.targets)
        )
        assignment.value = ast.Dict(
            keys=[ast.Constant(name) for name in self.expected],
            values=[ast.Constant(digest) for digest in self.expected.values()],
        )
        self.guard.write(self.path, ast.unparse(tree))
        self.guard.git('add', '--', self.path)
        self.guard.scan(1)


if __name__ == '__main__':
    unittest.main()
