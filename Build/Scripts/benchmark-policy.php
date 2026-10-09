<?php

/** SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH */
declare(strict_types=1);
use Composer\InstalledVersions;
use GuzzleHttp\Psr7\Request;
use Netresearch\HttpGuard\AddressClassifier;
use Netresearch\HttpGuard\GuardConfig;
use Netresearch\HttpGuard\NullDecisionReporter;
use Netresearch\HttpGuard\PolicyEngine;
use Netresearch\HttpGuard\PolicyRegistry;
use Netresearch\HttpGuard\Resolution;
use Netresearch\HttpGuard\ResolverInterface;
use Netresearch\HttpGuard\SystemClock;
use Netresearch\HttpGuard\TargetNormalizer;

require dirname(__DIR__, 2) . '/Tests/bootstrap.php';
$root     = dirname(__DIR__, 2);
$profiles = [];
for ($index = 0; $index < 128; ++$index) {
    $profiles['benchmark-' . $index] = [
        'origin'       => 'https://benchmark-' . $index . '.test',
        'allowedCidrs' => ['2001:4860:abcd:1::/64'],
        'methods'      => ['GET'],
        'purpose'      => 'Offline CI reference performance measurement',
        'owner'        => 'HTTP Guard maintainers',
    ];
}
$config    = GuardConfig::fromArray(['endpoints' => $profiles]);
$addresses = [];
for ($index = 1; $index <= 64; ++$index) {
    $addresses[] = '2001:4860:abcd:1::' . dechex($index);
}
$resolver = new class ($addresses) implements ResolverInterface {
    /** @param list<string> $addresses */
    public function __construct(private readonly array $addresses) {}

    public function resolve(string $canonicalHost): Resolution
    {
        if ($canonicalHost !== 'benchmark-127.test') {
            throw new LogicException('Unexpected benchmark host.');
        }

        return new Resolution($this->addresses, 'static', null, 'offline-benchmark');
    }
};
$clock    = new SystemClock();
$registry = new PolicyRegistry($config, $clock);
$context  = $registry->newContext('benchmark-127');
$engine   = new PolicyEngine(
    new TargetNormalizer(),
    new AddressClassifier(),
    $resolver,
    $registry,
    $clock,
    new NullDecisionReporter(),
);
$request = new Request('GET', 'https://benchmark-127.test/measure');
$check   = $engine->plan($request, $context);
if (count($check->addresses) !== 64 || count($config->data['endpoints']) !== 128) {
    throw new LogicException('Incomplete reference workload.');
}
$warmup       = 30;
$samples      = 500;
$measurements = [];
for ($index = 0; $index < $warmup + $samples; ++$index) {
    $started  = hrtime(true);
    $decision = $engine->evaluate($request, $context);
    $elapsed  = (hrtime(true) - $started) / 1000000;
    if ($decision->decision !== 'allow' || $decision->profileId !== 'benchmark-127') {
        throw new LogicException('Reference workload did not evaluate the bound profile.');
    }
    if ($index >= $warmup) {
        $measurements[] = $elapsed;
    }
}
sort($measurements, SORT_NUMERIC);
$production = [];
$iterator   = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/Classes', FilesystemIterator::SKIP_DOTS));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $production[substr($file->getPathname(), strlen($root) + 1)] = hash_file('sha256', $file->getPathname());
    }
}
ksort($production, SORT_STRING);
$graph = [];
foreach (['typo3/cms-core', 'guzzlehttp/guzzle', 'guzzlehttp/promises', 'guzzlehttp/psr7'] as $package) {
    $graph[$package] = InstalledVersions::isInstalled($package) ? InstalledVersions::getPrettyVersion($package) : null;
}
$cpu = null;
if (is_readable('/proc/cpuinfo')) {
    $cpuInfo = file_get_contents('/proc/cpuinfo');
    if (is_string($cpuInfo) && preg_match('/^model name\s*:\s*(.+)$/m', $cpuInfo, $matches) === 1) {
        $cpu = trim($matches[1]);
    }
}
$p95    = $measurements[(int) ceil($samples * 0.95) - 1];
$report = [
    'schemaVersion' => 1,
    'reference'     => getenv('HTTP_GUARD_PERFORMANCE_REFERENCE') ?: 'local exploratory run; CI qualification is recorded separately',
    'scope'         => 'Normalization, classification and policy evaluation; 64 IPv6 addresses, 128 profiles, bound endpoint; no DNS, network or log sink',
    'timestamp'     => gmdate('c'),
    'source'        => [
        'githubSha'            => getenv('GITHUB_SHA') ?: null,
        'productionTreeSha256' => hash('sha256', json_encode($production, JSON_THROW_ON_ERROR)),
        'policyRevision'       => $config->revision,
    ],
    'runtime' => [
        'php'        => PHP_VERSION,
        'os'         => php_uname(),
        'cpu'        => $cpu,
        'graph'      => $graph,
        'xdebugMode' => getenv('XDEBUG_MODE') ?: ini_get('xdebug.mode'),
    ],
    'samples'    => $samples,
    'warmup'     => $warmup,
    'addresses'  => count($addresses),
    'profiles'   => count($profiles),
    'p50_ms'     => $measurements[(int) ceil($samples * 0.5) - 1],
    'p95_ms'     => $p95,
    'max_ms'     => $measurements[$samples - 1],
    'target_ms'  => 2.0,
    'target_met' => $p95 <= 2.0,
];
$destination = $argv[1] ?? $root . '/.Build/reports/policy-performance.json';
if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0775, true) && !is_dir(dirname($destination))) {
    throw new RuntimeException('Cannot create benchmark report directory.');
}
if (file_put_contents(
    $destination,
    json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
) === false) {
    throw new RuntimeException('Cannot save benchmark report.');
}
printf(
    'Policy reference: p95 %.3f ms; target 2.000 ms; %d addresses / %d profiles / %d samples.\n',
    $p95,
    count($addresses),
    count($profiles),
    $samples,
);
exit($report['target_met'] ? 0 : 1);
