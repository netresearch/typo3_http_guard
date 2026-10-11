"""Pure input-binding guard for completed measurement receipts."""


# Effective entry/configuration/data inputs of this project's current native
# qualification. Do not require documentation or unrelated archived evidence.
REQUIRED_NATIVE_INPUTS = (
    'composer.json', 'ext_emconf.php', 'ext_localconf.php',
    'Configuration/Services.yaml', 'infection.native.json5', 'phpunit.xml',
    'Build/Mutation/Native/phpunit.xml', 'Build/Mutation/bootstrap.php',
    'Build/Scripts/run-native-tests.sh', 'Build/Scripts/runTests.conf',
    'Build/Scripts/assert-test-runtime.php',
    'Tests/bootstrap.php', 'Tests/HttpGuard/bootstrap.php',
    'Tests/HttpGuard/Integration/wire_server.py',
    'Tests/HttpGuard/Integration/prepare-wire.sh',
    'Tests/HttpGuard/Integration/Fixtures/run-missing-curl.py',
    'Tests/HttpGuard/Integration/certificates/ca.crt',
    'Tests/HttpGuard/Integration/certificates/ca.key',
    'Tests/HttpGuard/Integration/certificates/client.crt',
    'Tests/HttpGuard/Integration/certificates/client.key',
    'Tests/HttpGuard/Integration/certificates/server.crt',
    'Tests/HttpGuard/Integration/certificates/server.key',
    'Resources/Private/HttpGuard/data/security-corpus/address-rules.json',
    'Resources/Private/HttpGuard/data/security-corpus/address-cases.json',
    'Resources/Private/HttpGuard/data/security-corpus/endpoint-cases.json',
)


def missing_required_native_inputs(current_input_records, native_inputs):
    required = set(REQUIRED_NATIVE_INPUTS)
    required.update(
        name for name, record in current_input_records.items()
        if record['kind'] == 'file' and name.endswith('.php')
        and name.startswith(('Classes/', 'Tests/', 'Configuration/')))
    return sorted(required.difference(native_inputs))


def completed_native_thresholds_met(stats, exit_code):
    """Full-source gates require zero tool-discarded mutants as well as 90/90."""
    for count in ('skippedCount', 'ignoredCount'):
        if type(stats.get(count)) is not int or stats[count] < 0:
            raise RuntimeError(f'Completed final Native statistics lack a valid {count}')
    return (exit_code == 0 and stats['msi'] >= 90 and stats['coveredCodeMsi'] >= 90
            and stats['skippedCount'] == 0 and stats['ignoredCount'] == 0)


def validate_completed_native_binding(start, end, present_differences, missing_inputs, comment_proof):
    """Return only exact, already measured annotation exceptions.

    A measurement with changed tests/configuration is a different measurement;
    production equality alone cannot qualify it for the audited working tree.
    """
    if end is None:
        raise RuntimeError('Completed final Native score requires its actual complete source-end receipt')
    if start != end:
        raise RuntimeError('Final Native effective source changed during measurement')
    if missing_inputs:
        raise RuntimeError('Final Native snapshot omits actual current effective source/config/runner/data inputs: '
                           + ', '.join(missing_inputs))
    qualified = []
    for name, difference in present_differences.items():
        allowed = (
            name == 'Tests/HttpGuard/Unit/Policy/WireIoBoundaryContractTest.php'
            and comment_proof['same_executable_ast']
            and difference['native_sha256'] == comment_proof['measured']['bytes_sha256']
            and difference['mechanical_sha256'] == comment_proof['current']['bytes_sha256']
            and comment_proof['measured']['comments_removed_ast_sha256']
            == comment_proof['current']['comments_removed_ast_sha256'])
        if not allowed:
            raise RuntimeError(f'Unqualified final Native effective input differs from mechanical source: {name}')
        qualified.append(name)
    return qualified
