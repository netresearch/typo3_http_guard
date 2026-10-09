<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH.
 */
declare(strict_types=1);

$cases = [
    'hash_missing',
    'hash_unreadable',
    'classifier_missing',
    'classifier_unreadable',
    'classifier_corrupt',
    'classifier_wrong_shape',
    'classifier_bad_rule',
    'classifier_empty_rules',
];
$case = $argv[1] ?? '';
require getenv('HTTP_GUARD_TEST_AUTOLOAD') ?: dirname(__DIR__, 5) . '/.Build/vendor/autoload.php';
if (!in_array($case, $cases, true)) {
    exit(2);
}
$sourceRoot  = dirname(__DIR__, 5);
$root        = sys_get_temp_dir() . '/http-guard-bundle-' . bin2hex(random_bytes(12));
$classes     = ['AddressClassifier', 'GuardConfig', 'Cidr', 'NativeOperation', 'PolicyException', 'OutboundPolicyExceptionInterface'];
$directories = [
    '',
    '/Classes',
    '/Classes/HttpGuard',
    '/Resources',
    '/Resources/Private',
    '/Resources/Private/HttpGuard',
    '/Resources/Private/HttpGuard/data',
    '/Resources/Private/HttpGuard/data/security-corpus',
];
foreach ($directories as $directory) {
    mkdir($root . $directory, 0700);
}
$bundle = $root . '/Resources/Private/HttpGuard/data/security-corpus/address-rules.json';
foreach ($classes as $class) {
    copy($sourceRoot . '/Classes/HttpGuard/' . $class . '.php', $root . '/Classes/HttpGuard/' . $class . '.php');
}
spl_autoload_register(
    static function (string $class) use ($root, $classes): void {
        $prefix = 'Netresearch\HttpGuard\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $name = substr($class, strlen($prefix));
        if (in_array($name, $classes, true)) {
            require $root . '/Classes/HttpGuard/' . $name . '.php';
        }
    },
    prepend: true,
);
$result = [];
try {
    if (str_ends_with($case, 'unreadable')) {
        file_put_contents($bundle, '{}');
        chmod($bundle, 00);
        if (is_readable($bundle)) {
            $result = ['unsupported' => 'Filesystem permission denial unavailable'];
        }
    } elseif ($case === 'classifier_corrupt') {
        file_put_contents($bundle, '{sensitive-corrupt-bundle');
    } elseif ($case === 'classifier_wrong_shape') {
        file_put_contents($bundle, '{}');
    } elseif (in_array($case, ['classifier_bad_rule', 'classifier_empty_rules'], true)) {
        $data = json_decode(
            (string) file_get_contents($sourceRoot . '/Resources/Private/HttpGuard/data/security-corpus/address-rules.json'),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
        if ($case === 'classifier_bad_rule') {
            $data['iana_special_rules'][0]['class'] = ['not a class'];
        } else {
            $data['iana_special_rules'] = [];
        }
        file_put_contents($bundle, json_encode($data, JSON_THROW_ON_ERROR));
    }
    if ($result === []) {
        $warnings = [];
        $handler  = static function (int $severity, string $message) use (&$warnings): bool {
            $warnings[] = [$severity, $message];

            return true;
        };
        set_error_handler($handler);
        try {
            try {
                if (str_starts_with($case, 'hash_')) {
                    Netresearch\HttpGuard\GuardConfig::fromArray([]);
                } else {
                    new Netresearch\HttpGuard\AddressClassifier();
                }
                $result = ['accepted' => true];
            } catch (Throwable $failure) {
                $result = [
                    'class'   => $failure::class,
                    'reason'  => $failure instanceof Netresearch\HttpGuard\PolicyException ? $failure->reasonCode() : null,
                    'message' => $failure->getMessage(),
                ];
            }
            $current = set_error_handler(static fn (): bool => false);
            restore_error_handler();
            $result['handlerRestored'] = $handler === $current;
            $result['sourceIsolated']  = str_starts_with(
                (new ReflectionClass(
                    str_starts_with($case, 'hash_') ? Netresearch\HttpGuard\GuardConfig::class : Netresearch\HttpGuard\AddressClassifier::class,
                ))->getFileName(),
                $root . '/Classes/HttpGuard/',
            );
            $result['warnings'] = $warnings;
        } finally {
            restore_error_handler();
        }
    }
} finally {
    if (is_file($bundle)) {
        chmod($bundle, 0600);
        unlink($bundle);
    }
    foreach ($classes as $class) {
        unlink($root . '/Classes/HttpGuard/' . $class . '.php');
    }
    foreach (array_reverse($directories) as $directory) {
        rmdir($root . $directory);
    }
}
echo json_encode($result, JSON_THROW_ON_ERROR);
