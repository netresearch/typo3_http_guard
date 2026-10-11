"""Explicit, reversible public representations of frozen JSON captures.

Original capture bytes remain authoritative and are never rewritten here.
Declared proven synthetic-fixture scalar digests and actually rehashed
Native source/vendor maps acquire explicit SHA-256 types.
"""

from __future__ import annotations

import copy
import hashlib
import importlib.util
import json
from pathlib import Path
import re

TRANSFORMATION_ID = "public-captured-sha256-maps-v2"
METADATA = "_public_receipt"
NATIVE_SNAPSHOT = Path("/home/cybot/projects/http-guard-native-complete20")
# Plain checksum metadata puts its digest before the path, as sha256sum does.
FIXTURES = {path: digest for digest, path in (
    ("352c03de1c19e4a8eb803db73713d38d9c23cd658862db07529ba6f233eee592", "Tests/HttpGuard/Integration/certificates/ca.key"),
    ("e1a150875e3ef0e3117a9ef5c7969b64448c279b6d1cfd7a0f044478fa3c9e06", "Tests/HttpGuard/Integration/certificates/client.key"),
    ("ae691852afc79c13ba926ab28cd31c26b3aaeded2be5fa657ff624629ac74269", "Tests/HttpGuard/Integration/certificates/server.key"),
)}
CAPTURES = {
    "local-runner-proof/native-controls.json": {
        "sha256": "1f507434c2359aabaafc7fea8ef5d3382a8488949bebad58fdd3b3a83018117a",
        "semantic_sha256": "7af483461a4d52d27590857cfe1a1e11805206b10b86e024c0c057291df3bb2d",
        "sections": ("initial_repository_certificates", "final_repository_certificates"),
    },
    "native-mutation-final/source-mutation-start.json": {
        "sha256": "b5cca2cee12ae397ed56615b83c6892631e1714a5ac8b71ffb18036cd76ca81e",
        "semantic_sha256": "171400426a166b5fcee3760ebe1418e6aea9ef498c8c9fde62f5095e97f7beb2",
        "sections": ("sha256",),
    },
    "phase2-review/summary.json": {
        "sha256": "24328a583e2138bb451c706283a23135da415ec3491e87ab86348667300d4a3b",
        "semantic_sha256": "a02c0ce2c1fbe8ccaa0592b33fab4aa9f26169c55bacac40d9d51fdb29145384",
        "sections": ("tested_file_sha256",),
    },
    "native-assessment-complete-20/source-start.json": {
        "sha256": "89e20aa7229909f41349bf3e2f189f6e34976eecf85a7953b9ce952edb2fc0c5",
        "semantic_sha256": "12677159071970e726051c400346a367e15bdf565499654c6505cef5584a7791",
        "sections": (),
        "maps": ("files", "vendor_files"),
        "input_count": 261,
    },
    "native-assessment-complete-20/source-end.json": {
        "sha256": "82bcd0086da9e6ebb39b44ac37de3b60e2cc6f08c508224f2e0adf32b87e8ce4",
        "semantic_sha256": "791f1b4cee692802578321369e26c374ebf4bb3ba075eb595a27f73725eee434",
        "sections": (),
        "maps": ("files", "vendor_files"),
        "input_count": 261,
    },
    "native-assessment-complete-263/source-start.json": {
        "sha256": "31ebf393257bd1f7d6d9295c394ecb82538867ef4d105e1cab480a02e56dd242",
        "semantic_sha256": "41a831342acba3426581a866c88cf72e9572e87e1a3a7d09982adb2c881e7610",
        "sections": (),
        "maps": ("files", "vendor_files"),
        "input_count": 263,
    },
    "root-quality-layout-bound.json": {
        "sha256": "28a1722465e2291813cb385bee23520a06894aa64ac1509b09e8d3ae23019272",
        "semantic_sha256": "be530bb2bf1cec0a5ba53c028ca1808f437dce84135b1489cb0f8efe189583e9",
        "sections": ("files",),
    },
    "root-quality-layout-complete-bound.json": {
        "sha256": "5303047e16d346c2a5bbf6df475b4a2054481ed665d68524d6fd5044a527dc6e",
        "semantic_sha256": "ec8c7d1210a1ff93e5710ad970ab3e9e1ac49e521bd5a3c12b0876cad4fffca5",
        "sections": ("files",),
    },
    # Actual completed source-end bytes are now sealed; unrelated substitutions
    # must never fall back to publishing a flat, unvalidated raw map.
    "native-assessment-complete-263/source-end.json": {
        "sha256": "eeaadee8935af39cf14bab6f22cc0f365817c688e382b21150bb9b3dadf186f7",
        "semantic_sha256": "d150f0668d3f1aa04aed2e14939b654c980ad8aca42d3343dc5122dcad4720ee",
        "sections": (),
        "maps": ("files", "vendor_files"),
        "input_count": 263,
    },
    'completion-mechanical-layout-final/active-source-review.json': {'sha256': '7bb4d502b6834a6414b8bd9bc92115445b15fa11f68fe3453ef244403d6d6392', 'semantic_sha256': 'bfe42c22bf03486ae96b4bd9b755adf396223f72c4dc5d836283070f1f25663d', 'sections': (), 'source_maps': ('active_php_paths_inspected',), 'source_map_count': 174, 'source_revalidation_scope': 'all_frozen_working_inputs'},
    'root-current-unit-inputs.json': {'sha256': 'bad57361894ee64838cfa7ad8285563d2173dd93a7146286ada3df2acf212706', 'semantic_sha256': '8cc4d0cb5a284c933e7f35e554025ee3ed48545f99e80936bfc0df71ed2147c9', 'sections': (), 'source_maps': ('files',), 'source_map_count': 173, 'source_revalidation_scope': 'all_frozen_working_inputs'},
    'functional-final-combined/root-copy-inputs.json': {'sha256': 'c363320fc757da0782b05c7295e3bc13db48da77e1adfe4e086dccd8b5f9177f', 'semantic_sha256': '2376b606837452fe959a7dfbed96b632842728826b7c5940b1a36c12369b104b', 'sections': (), 'source_maps': ('',), 'source_map_count': 498, 'source_revalidation_scope': 'production_subset_only'},
    'native-assessment-final/source-start.json': {'sha256': 'c243fce8fa8b4a1837606d664223ebbf5f8bf20276c3a5b90d32b39cf17c85a1', 'semantic_sha256': 'b10e256834b6d1252a15de14db26468ba75acb477283ef6215fe119fb3b6278f', 'sections': (), 'source_maps': ('files',), 'source_map_count': 295, 'source_revalidation_scope': 'historical_captured_metadata_only'},
    'native-assessment-final/source-end.json': {'sha256': 'e11559e43b121b19bb02242973230f8758c1c4a24456582c07f05ae1807b6ae3', 'semantic_sha256': '7d76b43dfe22014fa62a7f5abdb7b57cfdad98380047b6f45f15015ec5ffc108', 'sections': (), 'source_maps': ('files',), 'source_map_count': 295, 'source_revalidation_scope': 'historical_captured_metadata_only'},
}


def sha256(data: bytes) -> str:
    return hashlib.sha256(data).hexdigest()


def canonical_sha256(value: object) -> str:
    return sha256(json.dumps(value, sort_keys=True, separators=(",", ":"), ensure_ascii=False).encode())


def validate_public_fixtures(root: Path) -> dict:
    """Execute the unchanged complete maintained fixture guard and rehash its keys."""
    checker = root / "Build/Scripts/check-synthetic-fixtures.py"
    spec = importlib.util.spec_from_file_location("maintained_synthetic_fixture_guard", checker)
    module = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(module)
    validation = module.check(root)
    actual = {name: sha256((root / name).read_bytes()) for name in FIXTURES}
    if actual != FIXTURES or validation.get("status") != "PASS":
        raise ValueError("Complete actual fixture validation failed")
    return {"checker_path": "Build/Scripts/check-synthetic-fixtures.py", "checker_sha256": sha256(checker.read_bytes()), "validation": validation, "actual_fixture_sha256": actual}


def public_path(name: str) -> str:
    if name not in CAPTURES:
        raise ValueError("Capture is outside the reviewed frozen originals")
    path = Path(name)
    return "current-evidence/derived-public/" + str(path.with_name(path.stem + ".derived.json"))


def field_paths(name: str) -> tuple[tuple[str, str], ...]:
    if name not in CAPTURES:
        raise ValueError("Capture is outside the reviewed frozen originals")
    return tuple((section, fixture) for section in CAPTURES[name]["sections"] for fixture in FIXTURES)


def pointer(path: tuple[str, str]) -> str:
    return "/" + "/".join(part.replace("~", "~0").replace("/", "~1") for part in path)


def typed_digest_map(values: dict[str, str]) -> dict:
    if not isinstance(values, dict) or any(
            not isinstance(path, str) or not isinstance(value, str)
            or not re.fullmatch(r"[0-9a-f]{64}", value)
            for path, value in values.items()):
        raise ValueError("Declared SHA-256 map contains a non-digest value")
    return {"algorithm": "SHA-256", "hex_path_pairs": [[value, path] for path, value in values.items()]}


def untyped_digest_map(record: dict) -> dict[str, str]:
    if set(record) != {"algorithm", "hex_path_pairs"} or record["algorithm"] != "SHA-256":
        raise ValueError("Unknown typed SHA-256 map representation")
    pairs = record["hex_path_pairs"]
    if not isinstance(pairs, list) or any(not isinstance(pair, list) or len(pair) != 2 for pair in pairs):
        raise ValueError("Malformed typed SHA-256 map pairs")
    values = {path: digest for digest, path in pairs}
    if len(values) != len(pairs) or typed_digest_map(values) != record:
        raise ValueError("Typed SHA-256 map loses entries, ordering or digest types")
    return values


def validate_native_maps(payload: dict, name: str) -> dict:
    """Read every declared source/vendor byte from the one frozen native snapshot."""
    maps = CAPTURES[name].get("maps", ())
    if not maps:
        return {}
    if payload.get("snapshot") != str(NATIVE_SNAPSHOT):
        raise ValueError("Native receipt does not name the one reviewed frozen snapshot")
    proof = {"kind": "actual_read_only_frozen_snapshot_hash_revalidation", "snapshot": str(NATIVE_SNAPSHOT), "maps": {}}
    for section in maps:
        values = payload.get(section)
        typed_digest_map(values)
        for relative, expected in values.items():
            path = Path(relative)
            if path.is_absolute() or ".." in path.parts:
                raise ValueError("Native digest path escapes the reviewed snapshot")
            actual = NATIVE_SNAPSHOT / path
            if not actual.resolve().is_relative_to(NATIVE_SNAPSHOT) or not actual.is_file():
                raise ValueError("Native digest path does not name an owned snapshot file")
            if sha256(actual.read_bytes()) != expected:
                raise ValueError("Known snapshot file bytes differ from declared Native digest")
        proof["maps"][section] = {"file_count": len(values), "semantic_map_sha256": canonical_sha256(values), "every_declared_file_read_and_matches": True}
    if (len(payload["files"]) != payload["effective_input_count"]
            or len(payload["vendor_files"]) != payload["vendor_file_count"]):
        raise ValueError("Native source/vendor counts disagree with their complete maps")
    if len(payload["files"]) != CAPTURES[name]["input_count"] or len(payload["vendor_files"]) != 21601:
        raise ValueError("Native maps are not the reviewed source/vendor inventory sizes")
    entry_points = {"ext_emconf.php", "ext_localconf.php"}
    if (entry_points & payload["files"].keys()) != (entry_points if CAPTURES[name]["input_count"] == 263 else set()):
        raise ValueError("Native entry-point coverage does not match the declared original scope")
    return proof


def capture_sha_maps(name: str) -> tuple[str, ...]:
    """The exact named full-capture SHA containers; no unknown field is converted."""
    profile = CAPTURES[name]
    return profile.get("maps", ()) or profile.get("source_maps", ()) or profile["sections"]


def validate_complete_quality_map(payload: dict, name: str) -> dict:
    """Actually rehash all850 known frozen working files; historical maps are metadata."""
    if name != "root-quality-layout-complete-bound.json":
        return {}
    snapshot = Path("/home/cybot/projects/http-guard-mechanical-layout-final")
    values = payload.get("files")
    typed_digest_map(values)
    if len(values) != 850 or payload.get("source_file_count") != 850:
        raise ValueError("Complete quality capture does not contain all850 source inputs")
    for relative, expected in values.items():
        path = Path(relative)
        actual = snapshot / path
        if path.is_absolute() or ".." in path.parts or not actual.resolve().is_relative_to(snapshot) or not actual.is_file():
            raise ValueError("Quality digest path is outside the known frozen source")
        if sha256(actual.read_bytes()) != expected:
            raise ValueError("Complete quality source bytes differ from the captured digest")
    return {"kind": "actual_read_only_frozen_snapshot_hash_revalidation", "snapshot": str(snapshot),
            "file_count": 850, "every_declared_file_read_and_matches": True,
            "semantic_map_sha256": canonical_sha256(values)}



def validate_declared_source_map(payload: dict, name: str) -> dict:
    """Rehash only the explicit frozen scope; historical maps remain captured data."""
    profile = CAPTURES[name]
    sections = profile.get("source_maps", ())
    if not sections:
        return {}
    snapshot = Path("/home/cybot/projects/http-guard-mechanical-layout-final")
    proof = {"kind": "pinned_historical_captured_sha_metadata", "maps": {}}
    for section in sections:
        values = payload if section == "" else payload.get(section)
        typed_digest_map(values)
        if len(values) != profile["source_map_count"]:
            raise ValueError("Declared source map has a different reviewed size")
        scope = profile["source_revalidation_scope"]
        checked = values if scope == "all_frozen_working_inputs" else {
            path: digest for path, digest in values.items()
            if path.startswith("Classes/") and path.endswith(".php")
        } if scope == "production_subset_only" else {}
        if scope == "production_subset_only" and len(checked) != 75:
            raise ValueError("Functional historical map lacks the75 measured production inputs")
        for relative, expected in checked.items():
            path = Path(relative)
            actual = snapshot / path
            if path.is_absolute() or ".." in path.parts or not actual.resolve().is_relative_to(snapshot) or not actual.is_file():
                raise ValueError("Declared source digest path is outside the known frozen source")
            if sha256(actual.read_bytes()) != expected:
                raise ValueError("Known frozen source bytes differ from the captured digest")
        proof["maps"][section] = {"recorded_file_count": len(values), "actually_rehashed_file_count": len(checked),
            "semantic_map_sha256": canonical_sha256(values), "scope": scope}
        if checked:
            proof["kind"] = "actual_read_only_declared_frozen_source_hash_revalidation"
            proof["snapshot"] = str(snapshot)
    return proof

def captured_map_qualification(name: str) -> str:
    if CAPTURES[name].get("source_maps"):
        scope = CAPTURES[name]["source_revalidation_scope"]
        if scope == "all_frozen_working_inputs":
            return "Every digest in the exact declared current source map was actually rehashed against the frozen850-file mechanical snapshot. The pinned original remains an execution/source receipt for its recorded time; later checksum-guard, baseline, manual and report changes retain separate current validation."
        if scope == "production_subset_only":
            return "All498 recorded Functional copy-input digests are preserved as pinned captured metadata; exactly75 production PHP digests are actually rehashed against the known frozen mechanical snapshot. The other423 recorded inputs are historical and are not claimed to match current bytes or to have been executed again."
        return "All295 recorded historical Native source digests are retained as exact pinned captured metadata. No current snapshot byte revalidation, fresh Native execution or retrospective before-run capture is inferred from this representation."
    if name == "root-quality-layout-complete-bound.json":
        return "Every declared working-file digest was actually rehashed against the frozen850-file mechanical snapshot. The original pinned completed quality receipt retains separate actual execution and vendor-before/end confirmation. Later generated bytes are outside this source freeze."
    if CAPTURES[name].get("maps"):
        return "Every declared Native source/vendor digest was actually rehashed against the one frozen measured Native snapshot. Manifest coverage and measurement completion remain separately qualified."
    return "These exact SHA-256 containers are retained historical captured metadata, pinned to the original raw and semantic JSON hashes. Typing their recorded values is not a fresh execution or retrospective source-hash capture. No unidentified credential field or unknown capture is converted."


def native_scope_qualification(name: str) -> dict:
    """Describe manifest coverage without inferring a successful measurement."""
    expected = CAPTURES[name]
    if not expected.get("maps"):
        return {}
    count = expected["input_count"]
    return {
        "source_manifest_input_count": count,
        "shared_root_input_count": count - 1,
        "snapshot_only_inputs": ["composer.lock"],
        "missing_before_run_entry_point_hashes": ["ext_emconf.php", "ext_localconf.php"] if count == 261 else [],
        "qualification": (
            "This original 261-input manifest omitted both PHP entry-point before-run hashes. Its genuine completed score remains a historical result with that binding limitation; it does not satisfy the complete required-input guard. The generated composer.lock belongs to the frozen snapshot; 260 other inputs are shared with Root."
            if count == 261 else
            "This original 263-input manifest includes both PHP entry-point hashes. The generated composer.lock belongs to the frozen snapshot; 262 other inputs are shared with Root. A start capture alone establishes no completed score, exit status or source-end result."
        ),
    }


def quality_scope_qualification(name: str) -> dict:
    """Retain the original incomplete binding separately from complete850."""
    if name == "root-quality-layout-bound.json":
        return {
            "before_run_working_file_count": 280,
            "complete_before_run_quality_input_closure": False,
            "missing_input_groups": ["Build/Mutation/bootstrap.php", "harness instruction inputs", "research header inputs"],
            "qualification": "Historical successful quality/control execution with an incomplete280-file before binding. Mutation bootstrap, harness instruction and research-header dependencies were omitted. It is not the final complete quality binding; no missing original before-run hashes are retrofitted.",
        }
    if name == "root-quality-layout-complete-bound.json":
        return {
            "before_run_working_file_count": 850,
            "installed_vendor_file_count": 21601,
            "complete_before_run_quality_input_closure": True,
            "qualification": "Actual successful quality and118-control executions have unchanged before/end hashes for the complete850-file tracked-plus-nonignored working snapshot and21601 installed vendor files. This is the pre-publication source freeze, not a claim that subsequent generated report/documentation bytes remain identical. The recorded logs and original raw hashes remain authoritative.",
        }
    return {}


def transform_fields(payload: dict, name: str, actual_fixture_hashes: dict[str, str]) -> dict:
    """Pure transformation; unidentified fields are never interpreted as fixtures."""
    if actual_fixture_hashes != FIXTURES:
        raise ValueError("Actual public fixture bytes are not the exact reviewed fixtures")
    if METADATA in payload:
        raise ValueError("An original capture cannot already contain derivative metadata")
    result = copy.deepcopy(payload)
    for section, fixture in field_paths(name):
        if result.get(section, {}).get(fixture) != FIXTURES[fixture]:
            raise ValueError("Capture's exact public-fixture digest field does not match")
        result[section][fixture] = {"algorithm": "SHA-256", "hex": FIXTURES[fixture]}
    return result


def derive(original_bytes: bytes, name: str, fixture_proof: dict) -> bytes:
    """Bind a deterministic public derivative to its exact original and fixture proof."""
    if name not in CAPTURES or not CAPTURES[name]["sha256"] or sha256(original_bytes) != CAPTURES[name]["sha256"]:
        raise ValueError("Original capture bytes differ from the frozen reviewed capture")
    if fixture_proof.get("validation", {}).get("status") != "PASS":
        raise ValueError("The complete maintained synthetic-fixture guard did not pass")
    payload = json.loads(original_bytes)
    if canonical_sha256(payload) != CAPTURES[name]["semantic_sha256"]:
        raise ValueError("Original semantic JSON differs from its frozen reviewed value")
    native_proof = validate_native_maps(payload, name)
    complete_quality_proof = validate_complete_quality_map(payload, name)
    declared_source_proof = validate_declared_source_map(payload, name)
    transformed = transform_fields(payload, name, fixture_proof.get("actual_fixture_sha256", {}))
    for section in capture_sha_maps(name):
        if section == "":
            transformed = typed_digest_map(payload)
        else:
            transformed[section] = typed_digest_map(payload[section])
    public_fixture_proof = copy.deepcopy(fixture_proof)
    public_fixture_proof["actual_fixture_sha256"] = typed_digest_map(public_fixture_proof["actual_fixture_sha256"])
    transformed[METADATA] = {
        "kind": "derived_public_receipt",
        "schema_version": 1,
        "original_capture": {
            "work_path": name,
            "bytes_sha256": CAPTURES[name]["sha256"],
            "semantic_json_sha256": canonical_sha256(payload),
            "preserved_unmodified_outside_active_repository": True,
        },
        "transformation": {
            "id": TRANSFORMATION_ID,
            "description": "Exact named captured SHA-256 map containers become algorithm-typed ordered [digest, path] records, with every plaintext digest and path unchanged. Declared public fixture fields are first validated against exact actual synthetic fixture bytes. Native source/vendor, complete850 quality, current173 Unit and174 active-source maps are actually rehashed against their known frozen snapshots. The Functional map retains498 recorded inputs with only its75 production inputs actually rehashed; historical295 Native maps retain only captured-metadata meaning. No unknown field or unpinned original is converted. Removing metadata and reversing only the declared containers reconstructs the complete original JSON value; actual data, counts and statuses are unchanged, while serialized bytes differ.",
            "json_pointers": [pointer(path) for path in field_paths(name)],
            "typed_sha256_map_containers": list(capture_sha_maps(name)),
            "changed_scalar_fields": 0,
            "validated_public_fixture_fields": len(field_paths(name)),
            "scanner_policy_changed": False,
        },
        "fixture_proof": public_fixture_proof,
        "native_snapshot_revalidation": native_proof,
        "complete_quality_snapshot_revalidation": complete_quality_proof,
        "declared_source_map_revalidation": declared_source_proof,
        "captured_sha_map_qualification": captured_map_qualification(name),
        "native_binding_scope": native_scope_qualification(name),
        "quality_binding_scope": quality_scope_qualification(name),
        "method": {
            "public_path": "current-evidence/derived-public/derived_public_receipts.py",
            "bytes_sha256": sha256(Path(__file__).read_bytes()),
        },
    }
    if reconstruct(transformed) != payload:
        raise ValueError("Public derivative does not reconstruct the complete original data")
    return (json.dumps(transformed, indent=2, ensure_ascii=False) + "\n").encode()


def reconstruct(derived: dict) -> dict:
    """Recover the complete original JSON value, validating every typed field."""
    result = copy.deepcopy(derived)
    metadata = result.pop(METADATA)
    name = metadata["original_capture"]["work_path"]
    expected = CAPTURES.get(name)
    if not expected or not expected["sha256"] or metadata["kind"] != "derived_public_receipt":
        raise ValueError("Unknown public receipt provenance")
    if metadata["original_capture"]["bytes_sha256"] != expected["sha256"]:
        raise ValueError("Original capture hash is not the frozen reviewed hash")
    if metadata["original_capture"]["semantic_json_sha256"] != expected["semantic_sha256"]:
        raise ValueError("Original semantic hash is not the frozen reviewed hash")
    if metadata["native_binding_scope"] != native_scope_qualification(name):
        raise ValueError("Native source binding qualification differs from the original scope")
    if metadata["quality_binding_scope"] != quality_scope_qualification(name):
        raise ValueError("Quality source binding qualification differs from its original scope")
    transformation = metadata["transformation"]
    if (transformation["id"] != TRANSFORMATION_ID
            or transformation["json_pointers"] != [pointer(path) for path in field_paths(name)]
            or transformation["typed_sha256_map_containers"] != list(capture_sha_maps(name))
            or transformation["changed_scalar_fields"] != 0
            or transformation["validated_public_fixture_fields"] != len(field_paths(name))):
        raise ValueError("Transformation is outside the declared reviewed fields")
    if metadata["captured_sha_map_qualification"] != captured_map_qualification(name):
        raise ValueError("Captured SHA map meaning differs from its reviewed scope")
    for section in capture_sha_maps(name):
        if section == "":
            result = untyped_digest_map(result)
        else:
            result[section] = untyped_digest_map(result[section])
    for section, fixture in field_paths(name):
        if result[section][fixture] != FIXTURES[fixture]:
            raise ValueError("Reconstructed fixture checksum differs from the exact original")
    if canonical_sha256(result) != metadata["original_capture"]["semantic_json_sha256"]:
        raise ValueError("Original semantic JSON digest does not match reconstruction")
    return result


def evidence_reference(name: str, derived_bytes: bytes, *, mtime: str, original_kind: str) -> dict:
    derived = json.loads(derived_bytes)
    reconstruct(derived)
    return {
        "path": public_path(name),
        "original_work_path": name,
        "sha256": sha256(derived_bytes),
        "file_mtime_utc": mtime,
        "kind": "derived_public_receipt",
        "original_capture_kind": original_kind,
        "provenance": copy.deepcopy(derived[METADATA]),
        "qualification": "The referenced public bytes are an explicitly derived representation, not a byte-identical original execution capture. The complete original JSON data is reversibly preserved and the unmodified raw capture remains authoritative outside the active repository.",
    }
