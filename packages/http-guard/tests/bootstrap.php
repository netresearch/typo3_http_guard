<?php

declare (strict_types=1);

$vendor = getenv('HTTP_GUARD_TEST_AUTOLOAD');
if ($vendor !== false && $vendor !== '') {
    require_once $vendor;
} elseif (is_file(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
} else {
    require_once dirname(__DIR__, 5) . '/work/library-tools/vendor/autoload.php';
}
spl_autoload_register(
    static function (string $class): void {
        $prefix = 'Netresearch\HttpGuard\\';
        if (!str_starts_with($class, $prefix)) {
            return;
        }
        $relative = substr($class, strlen($prefix));
        $path = str_starts_with($relative, 'Tests\\') ? __DIR__ . '/' . str_replace('\\', '/', substr($relative, 6)) . '.php' : dirname(__DIR__) . '/src/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($path)) {
            require_once $path;
        }
    },
    true,
    true
);
