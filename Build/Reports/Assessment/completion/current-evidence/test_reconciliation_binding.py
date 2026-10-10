"""Offline producer integrity controls; abstract gate values are not measured scores."""

import copy
import unittest

from reconciliation_binding import (REQUIRED_NATIVE_INPUTS,
                                    completed_native_thresholds_met,
                                    missing_required_native_inputs,
                                    validate_completed_native_binding)


class CompletedBindingTests(unittest.TestCase):
    def setUp(self):
        self.inputs = {'Classes/Example.php': 'production-sha', 'Tests/Example.php': 'test-sha'}
        self.comment_proof = {
            'same_executable_ast': True,
            'measured': {'bytes_sha256': 'measured-comment-sha', 'comments_removed_ast_sha256': 'actual-ast-sha'},
            'current': {'bytes_sha256': 'current-comment-sha', 'comments_removed_ast_sha256': 'actual-ast-sha'},
        }
        self.comment_path = 'Tests/HttpGuard/Unit/Policy/WireIoBoundaryContractTest.php'
        self.comment_delta = {self.comment_path: {'native_sha256': 'measured-comment-sha', 'mechanical_sha256': 'current-comment-sha'}}

    def check(self, end=None, changes=None, missing=None, proof=None):
        return validate_completed_native_binding(self.inputs, end, changes or {}, missing or [], proof or self.comment_proof)

    def test_identical_complete_effective_inputs_are_accepted(self):
        self.assertEqual([], self.check(dict(self.inputs)))

    def test_missing_end_receipt_is_rejected(self):
        with self.assertRaisesRegex(RuntimeError, 'source-end'):
            self.check()

    def test_changed_effective_end_input_is_rejected(self):
        end = dict(self.inputs)
        end['Tests/Example.php'] = 'different-test-semantics'
        with self.assertRaisesRegex(RuntimeError, 'changed during'):
            self.check(end)

    def test_added_or_missing_end_input_is_rejected(self):
        for end in ({'Classes/Example.php': 'production-sha'}, {**self.inputs, 'Tests/Added.php': 'added'}):
            with self.subTest(end=end), self.assertRaisesRegex(RuntimeError, 'changed during'):
                self.check(end)

    def test_unclassified_present_test_or_config_or_runner_difference_is_rejected(self):
        for path in ('Tests/Example.php', 'Build/Mutation/Native/phpunit.xml', 'Build/Scripts/run-native-tests.sh'):
            with self.subTest(path=path), self.assertRaisesRegex(RuntimeError, 'Unqualified'):
                self.check(dict(self.inputs), {path: {'native_sha256': 'old', 'mechanical_sha256': 'new'}})

    def test_missing_current_php_input_is_rejected(self):
        with self.assertRaisesRegex(RuntimeError, 'omits'):
            self.check(dict(self.inputs), missing=['Tests/NewContract.php'])

    def test_absent_native_xml_configuration_is_rejected_by_actual_presence_guard(self):
        native = dict.fromkeys(REQUIRED_NATIVE_INPUTS, 'fixture-sha')
        del native['Build/Mutation/Native/phpunit.xml']
        missing = missing_required_native_inputs({}, native)
        self.assertEqual(['Build/Mutation/Native/phpunit.xml'], missing)
        with self.assertRaisesRegex(RuntimeError, 'omits.*phpunit.xml'):
            self.check(dict(self.inputs), missing=missing)

    def test_absent_native_json_runner_wire_and_corpus_inputs_are_rejected(self):
        for name in ('infection.native.json5', 'Build/Scripts/run-native-tests.sh',
                     'Tests/HttpGuard/Integration/wire_server.py',
                     'Resources/Private/HttpGuard/data/security-corpus/address-rules.json'):
            with self.subTest(name=name):
                native = dict.fromkeys(REQUIRED_NATIVE_INPUTS, 'fixture-sha')
                del native[name]
                missing = missing_required_native_inputs({}, native)
                self.assertEqual([name], missing)
                with self.assertRaisesRegex(RuntimeError, 'omits'):
                    self.check(dict(self.inputs), missing=missing)

    def test_unrelated_documentation_and_archived_evidence_are_not_required(self):
        current = {'Documentation/Index.rst': {'kind': 'file'},
                   'Resources/Private/archive.json': {'kind': 'file'}}
        self.assertEqual([], missing_required_native_inputs(
            current, dict.fromkeys(REQUIRED_NATIVE_INPUTS, 'fixture-sha')))

    def test_tool_90_success_cannot_hide_skipped_or_ignored_mutants(self):
        stats = {'msi': 99.0, 'coveredCodeMsi': 99.0, 'skippedCount': 0, 'ignoredCount': 0}
        self.assertTrue(completed_native_thresholds_met(stats, 0))
        for field in ('skippedCount', 'ignoredCount'):
            with self.subTest(field=field):
                self.assertFalse(completed_native_thresholds_met({**stats, field: 1}, 0))
        self.assertFalse(completed_native_thresholds_met(stats, 1))
        self.assertFalse(completed_native_thresholds_met({**stats, 'msi': 89.99}, 0))
        self.assertFalse(completed_native_thresholds_met({**stats, 'coveredCodeMsi': 89.99}, 0))

    def test_missing_discarded_mutant_counts_cannot_authorize_completed_gate(self):
        for field in ('skippedCount', 'ignoredCount'):
            with self.subTest(field=field), self.assertRaisesRegex(RuntimeError, 'statistics lack'):
                completed_native_thresholds_met(
                    {'msi': 100, 'coveredCodeMsi': 100, 'skippedCount': 0, 'ignoredCount': 0}
                    | {field: None}, 0)

    def test_only_exact_known_hash_bound_ast_equal_comment_delta_is_accepted(self):
        self.assertEqual([self.comment_path], self.check(dict(self.inputs), self.comment_delta))

    def test_known_comment_filename_cannot_authorize_another_byte_delta(self):
        for field in ('native_sha256', 'mechanical_sha256'):
            altered = copy.deepcopy(self.comment_delta)
            altered[self.comment_path][field] = 'unknown-bytes'
            with self.subTest(field=field), self.assertRaisesRegex(RuntimeError, 'Unqualified'):
                self.check(dict(self.inputs), altered)

    def test_inequal_or_unconfirmed_executable_ast_is_rejected(self):
        for changed in ('same_executable_ast', 'comments_removed_ast_sha256'):
            proof = copy.deepcopy(self.comment_proof)
            if changed == 'same_executable_ast':
                proof[changed] = False
            else:
                proof['current'][changed] = 'different-executable-ast'
            with self.subTest(changed=changed), self.assertRaisesRegex(RuntimeError, 'Unqualified'):
                self.check(dict(self.inputs), self.comment_delta, proof=proof)


if __name__ == '__main__':
    unittest.main(verbosity=2)
