# SPDX-License-Identifier: GPL-2.0-or-later
# SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
"""Portable coverage keeps measurements while refusing unrelated source paths."""
import importlib.util
from pathlib import Path
import tempfile
import unittest
from defusedxml import ElementTree
from defusedxml.common import DefusedXmlException

SPEC = importlib.util.spec_from_file_location(
    'normalize_clover', Path(__file__).resolve().parents[2] / 'Build/Scripts/normalize-clover.py'
)
MODULE = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(MODULE)


class CoveragePathsTest(unittest.TestCase):
    def test_absolute_path_becomes_portable_without_altering_measured_lines(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            source = root / 'Classes/Policy.php'
            source.parent.mkdir()
            source.write_text('<?php')
            report = root / 'clover.xml'
            report.write_text(f'<coverage><project><file name="{source}"><line num="7" count="3" type="stmt"/></file><metrics statements="1" coveredstatements="1"/></project></coverage>')
            self.assertEqual(1, MODULE.normalize(report, root))
            tree = ElementTree.parse(report)
            self.assertEqual('Classes/Policy.php', tree.find('.//file').get('name'))
            self.assertEqual({'num': '7', 'count': '3', 'type': 'stmt'}, tree.find('.//line').attrib)
            self.assertEqual({'statements': '1', 'coveredstatements': '1'}, tree.find('.//metrics').attrib)
            self.assertEqual(1, MODULE.normalize(report, root))

    def test_unrelated_source_cannot_be_published_as_production_coverage(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            source = root / 'secret.php'
            source.write_text('<?php')
            report = root / 'clover.xml'
            report.write_text(f'<coverage><file name="{source}"/></coverage>')
            before = report.read_bytes()
            with self.assertRaises(ValueError):
                MODULE.normalize(report, root)
            self.assertEqual(before, report.read_bytes())

    def test_empty_report_is_a_failure(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            report = root / 'clover.xml'
            report.write_text('<coverage/>')
            with self.assertRaises(ValueError):
                MODULE.normalize(report, root)

    def test_document_type_is_rejected_before_report_changes(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            report = root / 'clover.xml'
            report.write_text('<!DOCTYPE coverage><coverage/>')
            before = report.read_bytes()
            with self.assertRaises(DefusedXmlException):
                MODULE.normalize(report, root)
            self.assertEqual(before, report.read_bytes())


if __name__ == '__main__':
    unittest.main()
