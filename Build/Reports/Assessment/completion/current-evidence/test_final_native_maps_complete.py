"""Pure producer controls; copied endpoints are fixtures, not another execution."""
import copy
import hashlib
import json
from pathlib import Path
import unittest

from final_native_maps_complete import validate_complete_native_maps

WORK = Path(__file__).resolve().parent
ROOT = Path("/home/cybot/projects/typo3-http-guard-assessment-work")


class FinalNativeMapTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        # The actual263 start is used only as an inventory/control input. Its
        # copied end and result below are explicitly abstract routing fixtures.
        cls.start = json.loads((WORK / "native-assessment-complete-263/source-start.json").read_bytes())
        cls.records = json.loads((WORK / "completion-mechanical-layout-final/source-binding.json").read_text())["files"]
        cls.vendors = json.loads((WORK / "root-quality-layout-complete-start.json").read_text())["vendor_files"]
        cls.actual_installed = hashlib.sha256((ROOT / ".Build/vendor/composer/installed.json").read_bytes()).hexdigest()
        cls.result = {"input_count": 263, "vendor_file_count": 21601,
                      "source_start_end_identical": True, "vendor_start_end_identical": True}

    def run_control(self, start=None, end=None, result=None, records=None):
        return validate_complete_native_maps(
            self.start if start is None else start,
            copy.deepcopy(self.start) if end is None else end,
            self.result if result is None else result,
            self.records if records is None else records, self.vendors, self.actual_installed)

    def test_copied_endpoint_control_qualifies_maps_without_any_score(self):
        proof = self.run_control()
        self.assertEqual(proof["shared_root_input_count"], 262)
        self.assertEqual(proof["snapshot_only_inputs"], ["composer.lock"])
        self.assertNotIn("statistics", proof)
        self.assertIn("does not execute", proof["qualification"])

    def test_absent_actual_end_is_rejected(self):
        with self.assertRaises(RuntimeError):
            validate_complete_native_maps(self.start, None, self.result, self.records, self.vendors, self.actual_installed)

    def test_changed_vendor_digest_is_rejected(self):
        end = copy.deepcopy(self.start)
        end["vendor_files"][next(iter(end["vendor_files"]))] = "c" * 64
        with self.assertRaises(RuntimeError):
            self.run_control(end=end)

    def test_absent_vendor_map_is_rejected(self):
        end = copy.deepcopy(self.start)
        del end["vendor_files"]
        with self.assertRaises(RuntimeError):
            self.run_control(end=end)

    def test_empty_vendor_maps_are_rejected(self):
        changed = copy.deepcopy(self.start)
        changed["vendor_files"] = {}
        changed["vendor_file_count"] = 0
        with self.assertRaises(RuntimeError):
            self.run_control(start=changed, end=changed)

    def test_changed_source_digest_is_rejected(self):
        end = copy.deepcopy(self.start)
        end["files"][next(iter(end["files"]))] = "c" * 64
        with self.assertRaises(RuntimeError):
            self.run_control(end=end)

    def test_disagreeing_declared_endpoint_counts_are_rejected(self):
        end = copy.deepcopy(self.start)
        end["vendor_file_count"] -= 1
        with self.assertRaises(RuntimeError):
            self.run_control(end=end)

    def test_disagreeing_result_counts_are_rejected(self):
        for field in ("input_count", "vendor_file_count"):
            changed = dict(self.result)
            changed[field] -= 1
            with self.assertRaises(RuntimeError):
                self.run_control(result=changed)

    def test_nonidentical_result_confirmation_is_rejected(self):
        for field in ("source_start_end_identical", "vendor_start_end_identical"):
            changed = dict(self.result)
            changed[field] = False
            with self.assertRaises(RuntimeError):
                self.run_control(result=changed)

    def test_unclassified_snapshot_only_input_is_rejected(self):
        records = dict(self.records)
        del records["composer.json"]
        with self.assertRaises(RuntimeError):
            self.run_control(records=records)

    def test_treating_snapshot_lock_as_shared_root_input_is_rejected(self):
        records = copy.deepcopy(self.records)
        records["composer.lock"] = {"kind": "file", "sha256": self.start["files"]["composer.lock"]}
        with self.assertRaises(RuntimeError):
            self.run_control(records=records)

    def test_shared_root_byte_difference_is_rejected(self):
        records = copy.deepcopy(self.records)
        records["composer.json"]["sha256"] = "c" * 64
        with self.assertRaises(RuntimeError):
            self.run_control(records=records)

    def test_missing_entry_point_before_hash_is_rejected(self):
        known_additional_root_input = "AGENTS.md"
        self.assertNotIn(known_additional_root_input, self.start["files"])
        additional_digest = hashlib.sha256((ROOT / known_additional_root_input).read_bytes()).hexdigest()
        for entry in ("ext_emconf.php", "ext_localconf.php"):
            changed = copy.deepcopy(self.start)
            changed["files"].pop(entry)
            changed["files"][known_additional_root_input] = additional_digest
            records = copy.deepcopy(self.records)
            records[known_additional_root_input] = {"kind": "file", "sha256": additional_digest}
            self.assertEqual(len(changed["files"]), 263)
            self.assertEqual(sorted(set(changed["files"]).difference(records)), [".Build/vendor/composer/installed.json", "composer.lock"])
            with self.assertRaises(RuntimeError):
                self.run_control(start=changed, end=changed, records=records)

    def test_actual_complete263_and_mechanical850_have_one_bound_vendor_metadata_input(self):
        end = json.loads((WORK / "native-assessment-complete-263/source-end.json").read_text())
        result = json.loads((WORK / "native-assessment-complete-263/measurement-result.json").read_text())
        proof = self.run_control(end=end, result=result)
        self.assertEqual(proof["shared_mechanical_working_file_count"], 261)
        self.assertEqual(proof["shared_root_installed_dependency_file_count"], 1)
        self.assertNotIn(".Build/vendor/composer/installed.json", self.records)
        self.assertTrue(proof["installed_dependency_binding"]["matches_native_source_and_vendor_maps_and_current_root_bytes"])

    def test_unbound_captured_vendor_metadata_is_rejected(self):
        for changed in ({}, dict(self.vendors, **{"composer/installed.json": "c" * 64})):
            with self.assertRaises(RuntimeError):
                validate_complete_native_maps(self.start, copy.deepcopy(self.start), self.result, self.records, changed, self.actual_installed)

    def test_current_root_installed_metadata_difference_is_rejected(self):
        with self.assertRaises(RuntimeError):
            validate_complete_native_maps(self.start, copy.deepcopy(self.start), self.result, self.records, self.vendors, "c" * 64)

    def test_native_source_vendor_installed_metadata_disagreement_is_rejected(self):
        changed = copy.deepcopy(self.start)
        changed["vendor_files"][".Build/vendor/composer/installed.json"] = "c" * 64
        with self.assertRaises(RuntimeError):
            self.run_control(start=changed, end=changed)

    def test_invented_mechanical_vendor_metadata_is_rejected(self):
        records = dict(self.records)
        records[".Build/vendor/composer/installed.json"] = {"kind": "file", "sha256": self.actual_installed}
        with self.assertRaises(RuntimeError):
            self.run_control(records=records)

    def test_genuine_historical261_manifest_cannot_be_final_complete_binding(self):
        historical = json.loads((WORK / "native-assessment-complete-20/source-start.json").read_bytes())
        end = json.loads((WORK / "native-assessment-complete-20/source-end.json").read_bytes())
        result = json.loads((WORK / "native-assessment-complete-20/measurement-result.json").read_bytes())
        with self.assertRaises(RuntimeError):
            self.run_control(start=historical, end=end, result=result)


if __name__ == "__main__":
    unittest.main(verbosity=2)
