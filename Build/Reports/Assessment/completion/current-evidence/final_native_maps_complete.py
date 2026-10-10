"""Pure validation of complete final Native maps, without executing workloads."""


def validate_complete_native_maps(start, end, result, current_records, root_vendor_before, actual_root_installed_sha256):
    """Distinguish immutable measured vendor/source bytes and snapshot-only lock."""
    if end is None:
        raise RuntimeError("A completed Native measurement requires actual source-end maps")
    for section, count_key in (("files", "effective_input_count"), ("vendor_files", "vendor_file_count")):
        initial = start.get(section)
        final = end.get(section)
        if not isinstance(initial, dict) or not initial or initial != final:
            raise RuntimeError("Native " + section + " maps are missing, empty or changed during measurement")
        if start.get(count_key) != len(initial) or end.get(count_key) != len(final):
            raise RuntimeError("Native declared map counts differ from actual entries")
    if len(start["files"]) != 263 or len(start["vendor_files"]) != 21601:
        raise RuntimeError("Final Native maps do not cover the actual full 263/21601 scope")
    if result.get("input_count") != 263 or result.get("vendor_file_count") != 21601:
        raise RuntimeError("Completed result does not record its complete measured input counts")
    if result.get("source_start_end_identical") is not True or result.get("vendor_start_end_identical") is not True:
        raise RuntimeError("Completed result does not confirm actual unchanged source/vendor endpoints")
    installed = ".Build/vendor/composer/installed.json"
    before_digest = root_vendor_before.get("composer/installed.json")
    if (len(root_vendor_before) != 21601 or not before_digest
            or start["files"].get(installed) != before_digest
            or start["vendor_files"].get(installed) != before_digest
            or actual_root_installed_sha256 != before_digest):
        raise RuntimeError("Native installed metadata lacks its exact captured Root/vendor/current byte binding")
    if installed in current_records:
        raise RuntimeError("Installed dependency metadata must not be fabricated as a mechanical working-file input")
    classified_records = dict(current_records)
    classified_records[installed] = {"kind": "file", "sha256": before_digest}
    snapshot_only = sorted(set(start["files"]).difference(classified_records))
    if snapshot_only != ["composer.lock"]:
        raise RuntimeError("Final Native snapshot-only inputs are not exactly the generated Composer lock")
    shared = {name: digest for name, digest in start["files"].items() if name in classified_records}
    if len(shared) != 262 or any(classified_records[name].get("kind") != "file"
                                 or classified_records[name].get("sha256") != digest
                                 for name, digest in shared.items()):
        raise RuntimeError("Final Native shared Root input bytes do not match the frozen mechanical source")
    if not {"ext_emconf.php", "ext_localconf.php"}.issubset(shared):
        raise RuntimeError("Final Native before-run entry-point hashes are missing")
    return {
        "effective_input_count": 263,
        "vendor_file_count": 21601,
        "shared_root_input_count": 262,
        "shared_mechanical_working_file_count": 261,
        "shared_root_installed_dependency_file_count": 1,
        "installed_dependency_binding": {"path": installed, "sha256": before_digest, "captured_quality_before_vendor_key": "composer/installed.json", "matches_native_source_and_vendor_maps_and_current_root_bytes": True},
        "snapshot_only_inputs": snapshot_only,
        "source_start_end_identical": True,
        "vendor_start_end_identical": True,
        "all_shared_working_file_bytes_match_mechanical_snapshot": True,
        "all_shared_root_bytes_match_their_explicit_captured_bindings": True,
        "qualification": "All 263 measured source/test/configuration inputs and 21,601 installed vendor files have byte-identical start/end maps. Both PHP entry-point before-run hashes are included. 261 shared working-file inputs match the mechanical snapshot exactly. One installed dependency metadata input separately matches the actual pinned quality-before vendor map, the Native source/vendor maps and current Root bytes; it is not an invented mechanical record. The generated composer.lock alone belongs only to the measured snapshot. This validates map binding and does not execute Native tests or infer a score.",
    }
