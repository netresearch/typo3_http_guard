"""Pure-data controls on the actual complete850 captures; execute no workload."""
import copy
import hashlib
import json
from pathlib import Path
import unittest
from final_quality_complete import validate_complete_quality_binding, QUALITY_NAME, START_NAME

WORK=Path(__file__).resolve().parent

def sha(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()

class CompleteQualityTests(unittest.TestCase):
    def setUp(self):
        self.quality=json.loads((WORK/QUALITY_NAME).read_text())
        self.start=json.loads((WORK/START_NAME).read_text())
        self.records=json.loads((WORK/'completion-mechanical-layout-final/source-binding.json').read_text())['files']
        self.name=QUALITY_NAME
        self.quality_sha=sha(WORK/QUALITY_NAME)
        self.start_sha=sha(WORK/START_NAME)
    def check(self):
        return validate_complete_quality_binding(self.name,self.quality,self.quality_sha,self.start,self.start_sha,self.records)
    def reject(self):
        with self.assertRaises(ValueError): self.check()
    def test_actual_pinned_full_binding_is_accepted(self):
        value=self.check()
        self.assertEqual(value['source_file_count'],850)
        self.assertTrue(value['all_mechanical_working_files_match_before_and_after'])
    def test_partial280_original_route_is_rejected(self):
        self.name='root-quality-layout-bound.json'; self.reject()
    def test_changed_quality_original_bytes_rejected(self):
        self.quality_sha='0'*64; self.reject()
    def test_changed_start_original_bytes_rejected(self):
        self.start_sha='0'*64; self.reject()
    def test_unbound_before_capture_is_rejected(self):
        self.quality['source_start_receipt_sha256']='0'*64; self.reject()
    def test_missing_agent_harness_input_is_rejected(self):
        self.quality['files'].pop('AGENTS.md'); self.reject()
    def test_changed_copilot_input_is_rejected(self):
        self.start['files']['.github/copilot-instructions.md']='0'*64; self.reject()
    def test_changed_research_header_input_is_rejected(self):
        name=next(n for n in self.start['files'] if n.startswith('Resources/') and n.endswith('.headers.txt'))
        self.start['files'][name]='0'*64; self.reject()
    def test_missing_root_input_is_rejected(self):
        self.records.pop('AGENTS.md'); self.reject()
    def test_changed_root_input_is_rejected(self):
        self.records['AGENTS.md']['sha256']='0'*64; self.reject()
    def test_wrong_declared850_count_is_rejected(self):
        self.quality['source_file_count']=849; self.reject()
    def test_missing_vendor_input_is_rejected(self):
        self.start['vendor_files'].pop(next(iter(self.start['vendor_files']))); self.reject()
    def test_malformed_vendor_digest_is_rejected(self):
        self.start['vendor_files'][next(iter(self.start['vendor_files']))]='bad'; self.reject()
    def test_failed_or_changed_completion_is_rejected(self):
        for key,value in [('exit_code',1),('controls_exit_code',1),('source_start_end_identical',False),('vendor_start_end_identical',False),('vendor_files_count',21600)]:
            with self.subTest(key=key):
                old=self.quality[key]; self.quality[key]=value; self.reject(); self.quality[key]=old

if __name__=='__main__': unittest.main(verbosity=2)
