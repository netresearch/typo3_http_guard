import http.client
import json
from pathlib import Path
import os
import subprocess

import argparse

parser = argparse.ArgumentParser(description='Run the actual TYPO3/Core/Guzzle four-cell production matrix.')
parser.add_argument('--runtime', required=True, type=Path)
parser.add_argument('--evidence', required=True, type=Path)
parser.add_argument('--classic', action='store_true', help='Use ZIP-extracted extension and official Core distribution in classic13/classic14 fixtures.')
arguments = parser.parse_args()
package = Path(__file__).resolve().parents[2]
runtime = arguments.runtime.resolve()
evidence = arguments.evidence.resolve()
evidence.mkdir(parents=True, exist_ok=True)
summary = []

def counters():
    result = []
    for ip in ('203.0.114.102', '10.23.5.12'):
        connection = http.client.HTTPConnection(ip, 8080, timeout=3)
        connection.request('GET', '/counters')
        raw = json.loads(connection.getresponse().read())
        connection.close()
        result.append((raw['requests'], raw['targetTcpAccepts']))
    return result

def run(fixture, case, command, expected, label, offline=False):
    environment = dict(os.environ,
        TYPO3_PATH_ROOT=str((runtime / fixture if arguments.classic else runtime / fixture / 'public').resolve()),
        TYPO3_PATH_APP=str((runtime / fixture).resolve()),
        HTTP_GUARD_BOOT_TEST=case,
    )
    before = counters()
    process = subprocess.run(command, env=environment, text=True, capture_output=True, timeout=30)
    (evidence / f'{fixture}-{label}.stdout').write_text(process.stdout)
    (evidence / f'{fixture}-{label}.stderr').write_text(process.stderr)
    try:
        report = json.loads(process.stdout)
    except json.JSONDecodeError:
        raise SystemExit(f'{fixture}/{label}: invalid JSON exit{process.returncode}: {process.stdout[:200]}')
    if process.returncode != expected or (offline and counters() != before):
        raise SystemExit(f'{fixture}/{label}: exit{process.returncode}, expected{expected}; wire={counters()} vs {before}')
    if expected == 0 and report.get('status') == 'FAIL':
        raise SystemExit(f'{fixture}/{label}: {report}')
    after = counters()
    summary.append({'fixture': fixture, 'case': case, 'label': label,
                    'exitCode': process.returncode, 'zeroNewTargetTcpAndHttp': after == before if offline else None,
                    'wireBefore': before, 'wireAfter': after,
                    'checks': len(report.get('checks', []))})
    print(f'{fixture}/{label}: PASS exit{process.returncode}', flush=True)

fixtures = ('classic13', 'classic14') if arguments.classic else ('core13g7', 'core13g8', 'core14g7', 'core14g8')
for fixture in fixtures:
    classic_argument = ['classic'] if arguments.classic else []
    run(fixture, 'normal', ['php', str(package / 'Tests/Integration/production-bootstrap.php'), str(runtime / fixture)] + classic_argument, 0, 'wire-final')
    for mode in ('observe', 'disabled'):
        run(fixture, mode, ['php', str(package / 'Tests/Integration/mode-bootstrap.php'), str(runtime / fixture), mode] + classic_argument, 0, mode + '-wire-final')
    run(fixture, 'factory-conflict', ['php', str(package / 'Tests/Integration/production-bootstrap.php'), str(runtime / fixture)] + classic_argument, 1, 'ordinary-boot-factory-conflict-final', offline=True)
    cli = ['php', str(runtime / fixture / ('typo3/sysext/core/bin/typo3' if arguments.classic else 'vendor/bin/typo3'))]
    for case in ('normal', 'invalid-mode', 'invalid-schema', 'object', 'factory-conflict', 'observe', 'disabled'):
        for command in ('doctor', 'config-check', 'legacy-report', 'policy-check'):
            cli_arguments = ['http-guard:' + command]
            if command == 'policy-check':
                cli_arguments += ['http://guard.test:8080/a', '--no-dns']
            expected = 3 if case in ('invalid-mode', 'invalid-schema', 'object', 'factory-conflict') else (2 if command == 'doctor' and case in ('observe', 'disabled') else 0)
            run(fixture, case, cli + cli_arguments, expected, case + '-' + command + '-final', offline=True)
    run(fixture, 'normal', cli + ['http-guard:policy-check', 'http://127.0.0.1/a', '--no-dns'], 2, 'policy-private-deny-final', offline=True)
    run(fixture, 'normal', cli + ['http-guard:policy-check', 'http://not-configured.test/a', '--no-dns'], 4, 'policy-unverified-final', offline=True)
    run(fixture, 'normal', cli + ['http-guard:policy-check', 'http://erp.test:8080/a', '--endpoint=erp-orders', '--no-dns'], 0, 'policy-explicit-endpoint-final', offline=True)
    run(fixture, 'normal', cli + ['http-guard:policy-check', 'http://user:fake-url-secret@guard.test:8080/a', '--no-dns'], 2, 'policy-userinfo-redacted-final', offline=True)
    run(fixture, 'normal', cli + ['http-guard:policy-check', 'http://guard.test:8080/a#', '--no-dns'], 2, 'policy-empty-fragment-final', offline=True)
    run(fixture, 'normal', cli + ['http-guard:policy-check', 'http://guard.test:8080/a\\b', '--no-dns'], 2, 'policy-raw-backslash-final', offline=True)
    run(fixture, 'normal', [cli[0], '-d', 'disable_functions=curl_multi_exec'] + cli[1:] + ['http-guard:doctor'], 3, 'doctor-missing-curl-multi-final', offline=True)
    run(fixture, 'normal', ['env', 'No_PrOxY=synthetic-fixture-proxy-exclusion'] + cli + ['http-guard:doctor'], 3, 'doctor-mixed-no-proxy-final', offline=True)
    for diagnostic in ('doctor', 'config-check'):
        run(fixture, 'review-overdue', cli + ['http-guard:' + diagnostic], 0, 'review-overdue-' + diagnostic + '-final', offline=True)
        report = json.loads((evidence / f'{fixture}-review-overdue-{diagnostic}-final.stdout').read_text())
        if report['warnings']['overdueEndpointReviews'] != ['erp-orders']:
            raise SystemExit(f'{fixture}: required review warning missing')


(evidence / 'production-matrix-summary.json').write_text(json.dumps({'status': 'PASS', 'runs': summary}, indent=2) + '\n')
