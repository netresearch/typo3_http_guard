#!/usr/bin/env python3
# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Command recognition and bounded execution for malformed inline snippets."""
import importlib.util
from pathlib import Path
import subprocess
import sys
import unittest


SCRIPT = Path(__file__).resolve().parents[2] / 'Build/Scripts/verify-harness.py'
SPEC = importlib.util.spec_from_file_location('http_guard_harness', SCRIPT)
HARNESS = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(HARNESS)


class HarnessCommandParsingTest(unittest.TestCase):
    def test_plain_text_has_no_inline_composer_command(self):
        self.assertEqual([], HARNESS.composer_commands('composer check:harness'))

    def test_install_and_project_commands_preserve_their_order(self):
        self.assertEqual(
            ['install', 'check:local', 'ci:test:php:phpstan'],
            HARNESS.composer_commands(
                '`composer install` then `composer check:local --verbose` and '
                '`composer ci:test:php:phpstan -c Build/custom.neon`'),
        )

    def test_command_alphabet_includes_digits_underscores_and_hyphens(self):
        self.assertEqual(['ci:test_2-php'], HARNESS.composer_commands('`composer ci:test_2-php`'))

    def test_empty_or_invalid_command_prefix_is_not_recognized(self):
        self.assertEqual([], HARNESS.composer_commands('`composer ` `composer !` `composer +option`'))

    def test_unterminated_command_is_not_recognized(self):
        self.assertEqual([], HARNESS.composer_commands('`composer check:harness'))

    def test_quoted_option_backtick_preserves_existing_closing_boundary(self):
        self.assertEqual(['check:harness'], HARNESS.composer_commands('`composer check:harness --value="`"`'))

    def test_prefix_before_punctuation_preserves_existing_recognition(self):
        self.assertEqual(['check:harness'], HARNESS.composer_commands('`composer check:harness.example`'))

    def test_invalid_prefix_does_not_hide_the_next_valid_opening(self):
        self.assertEqual(['check:harness'], HARNESS.composer_commands('`composer !`composer check:harness`'))

    def test_command_options_can_span_lines(self):
        self.assertEqual(['check:local'], HARNESS.composer_commands('`composer check:local\n --verbose`'))

    def test_long_unterminated_command_finishes_with_no_result(self):
        program = (
            'import importlib.util, sys; '
            'spec = importlib.util.spec_from_file_location("harness", sys.argv[1]); '
            'module = importlib.util.module_from_spec(spec); '
            'spec.loader.exec_module(module); '
            'sys.stdout.write(repr(module.composer_commands(sys.stdin.read())))'
        )
        result = subprocess.run(
            [sys.executable, '-c', program, str(SCRIPT)],
            input='`composer ' + 'a' * 500000,
            text=True, capture_output=True, timeout=10, check=False,
        )
        self.assertEqual(0, result.returncode, result.stderr)
        self.assertEqual('[]', result.stdout)


if __name__ == '__main__':
    unittest.main()
