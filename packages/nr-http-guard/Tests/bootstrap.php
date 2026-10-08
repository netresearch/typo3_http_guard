<?php

declare (strict_types=1);
$autoload = getenv('HTTP_GUARD_TEST_AUTOLOAD') ?: dirname(__DIR__) . '/vendor/autoload.php';
require $autoload;
spl_autoload_register(
    static function (string $class): void {
        $roots = [
            'Netresearch\NrHttpGuard\\' => dirname(__DIR__) . '/Classes/',
            'Netresearch\HttpGuard\\' => dirname(__DIR__, 2) . '/http-guard/src/',
        ];
        foreach ($roots as $prefix => $root) {
            if (str_starts_with($class, $prefix)) {
                $path = $root . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
                if (is_file($path)) {
                    require $path;
                }
                return;
            }
        }
    },
    prepend: true
);
