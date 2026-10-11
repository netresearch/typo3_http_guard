"""Verify the actual complete pre-publication quality captures, without running work."""
from __future__ import annotations
import re

QUALITY_NAME = 'root-quality-layout-complete-bound.json'
QUALITY_SHA256 = '5303047e16d346c2a5bbf6df475b4a2054481ed665d68524d6fd5044a527dc6e'
START_NAME = 'root-quality-layout-complete-start.json'
START_SHA256 = 'b54f500fe090b199b93feabee6e171a2e6cb44be18c12147dd3090ccf12f77d5'


def validate_complete_quality_binding(name, quality, quality_bytes_sha256, start, start_bytes_sha256, current_records):
    """Reject partial/substituted captures; compare every frozen working-file digest."""
    if name != QUALITY_NAME or quality_bytes_sha256 != QUALITY_SHA256 or start_bytes_sha256 != START_SHA256:
        raise ValueError('Complete quality requires the two actual pinned original captures')
    if quality.get('source_start_receipt_sha256') != START_SHA256:
        raise ValueError('Complete quality does not reference its actual before-run capture')
    expected = {path: record['sha256'] for path, record in current_records.items() if record.get('kind') == 'file'}
    if len(expected) != 850 or quality.get('source_file_count') != 850 or quality.get('files') != expected or start.get('files') != expected:
        raise ValueError('Complete quality before/end maps must equal all850 mechanical working-file inputs')
    vendors = start.get('vendor_files')
    if not isinstance(vendors, dict) or len(vendors) != 21601 or any(not isinstance(path, str) or not isinstance(digest, str) or not re.fullmatch('[0-9a-f]{64}', digest) for path, digest in vendors.items()):
        raise ValueError('Complete quality actual before-run vendor inventory is incomplete')
    if (quality.get('exit_code') != 0 or quality.get('controls_exit_code') != 0
            or quality.get('source_start_end_identical') is not True
            or quality.get('vendor_start_end_identical') is not True
            or quality.get('vendor_files_count') != 21601):
        raise ValueError('Complete quality must prove successful execution and unchanged complete inputs')
    return {
        'kind': 'actual_pinned_complete_pre_publication_quality_binding',
        'source_file_count': 850, 'all_mechanical_working_files_match_before_and_after': True,
        'original_quality_capture_sha256': quality_bytes_sha256,
        'original_start_capture_work_path': START_NAME, 'original_start_capture_sha256': start_bytes_sha256,
        'vendor_file_count': 21601, 'successful_quality_and_controls': True,
        'qualification': 'All850 tracked-plus-nonignored working-file hashes match the pre-publication mechanical snapshot; original pinned before-run capture retains21601 installed dependency hashes and the pinned completed capture confirms them unchanged. This does not claim that subsequently generated reports or documentation remain byte-identical. The earlier280 receipt is incomplete history, not the final quality gate.'}
