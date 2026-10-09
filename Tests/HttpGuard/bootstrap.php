<?php

declare (strict_types=1);
$extensionRoot = dirname(__DIR__, 2);
$vendor = getenv('HTTP_GUARD_TEST_AUTOLOAD') ?: $extensionRoot . '/vendor/autoload.php';
require_once $vendor;
spl_autoload_register(
    static function (string $class) use ($extensionRoot): void {
        $roots = [
            'Netresearch\HttpGuard\Tests\\' => __DIR__ . '/',
            'Netresearch\HttpGuard\\' => $extensionRoot . '/Classes/HttpGuard/',
        ];
        foreach ($roots as $prefix => $root) {
            if (str_starts_with($class, $prefix)) {
                $path = $root . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (is_file($path)) {
                    require_once $path;
                }
                return;
            }
        }
    },
    prepend: true
);
