<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Package\PackageSetup;

$fixture = realpath($argv[1]);
$phase   = $argv[2] ?? 'active';
putenv('TYPO3_PATH_ROOT=' . $fixture);
putenv('TYPO3_PATH_APP=' . $fixture);
$loader          = require $fixture . '/vendor/autoload.php';
$initialPrefixes = $loader->getPrefixesPsr4();
$namespaces      = ['Netresearch\NrHttpGuard\\', 'Netresearch\HttpGuard\\'];
$checks          = [];
function classicCheck(bool $value, string $name, array $details = []): void
{
    global $checks;
    if (!$value) {
        throw new RuntimeException('ASSERTION_FAILED: ' . $name);
    }
    $checks[] = ['check' => $name, 'status' => 'PASS', 'details' => $details];
}
try {
    foreach ($namespaces as $namespace) {
        classicCheck(!isset($initialPrefixes[$namespace]), 'not_registered_by_core_vendor_composer_' . $namespace);
    }
    SystemEnvironmentBuilder::run(0, SystemEnvironmentBuilder::REQUESTTYPE_CLI);
    classicCheck(!Environment::isComposerMode(), 'actual_classic_environment_without_composer_mode_override');
    $container = Bootstrap::init($loader);
    $manager   = $container->get(PackageManager::class);
    $manager->scanAvailablePackages();
    classicCheck($manager->isPackageAvailable('nr_http_guard'), 'actual_core_package_scan_finds_extracted_extension');
    $package       = $manager->getPackage('nr_http_guard');
    $extensionRoot = $fixture . '/typo3conf/ext/nr_http_guard/';
    classicCheck(
        realpath($package->getPackagePath()) === realpath($extensionRoot),
        'package_is_zip_extraction_without_source_symlink',
    );
    if ($phase === 'prepare') {
        classicCheck(
            !$manager->isPackageActive('nr_http_guard'),
            'extension_inactive_before_extension_manager_activation',
        );
        if (class_exists(PackageSetup::class)) {
            $container->get(PackageSetup::class)->setup([]);
        } else {
            $container->get(TYPO3\CMS\Core\Package\PackageActivationService::class)->updateDatabase();
        }
        classicCheck(is_file($fixture . '/test.sqlite'), 'real_core_schema_setup_on_disposable_sqlite');
    } else {
        classicCheck($manager->isPackageActive('nr_http_guard'), 'extension_manager_persisted_active_package');
        $generated = $fixture . '/typo3conf/autoload/autoload_psr4.php';
        classicCheck(is_file($generated), 'core_generated_native_autoload_file');
        $mappings = require $generated;
        foreach ($namespaces as $namespace) {
            classicCheck(
                isset($mappings[$namespace]),
                'core_generated_namespace_' . $namespace,
                ['paths' => $mappings[$namespace] ?? []],
            );
        }
        foreach ([Netresearch\NrHttpGuard\Http\MiddlewareRegistry::class, Netresearch\HttpGuard\AddressClassifier::class] as $class) {
            $file = (new ReflectionClass($class))->getFileName();
            classicCheck(
                is_string($file) && str_starts_with($file, $extensionRoot),
                'native_autoloaded_class_from_zip_' . $class,
                ['file' => $file],
            );
        }
        classicCheck(
            !is_file($extensionRoot . 'vendor/autoload.php') && !is_dir($extensionRoot . 'packages'),
            'one_extension_has_no_private_vendor_or_split_packages',
        );
        Netresearch\HttpGuard\Transport\RuntimeSupport::assertSupported();
        classicCheck(true, 'official_core_bundled_exact_http_sdk_tuple_supported');
        $factory = $container->get(TYPO3\CMS\Core\Http\RequestFactory::class);
        classicCheck(
            $factory::class === Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility::replacementClass(),
            'actual_core_raw_factory_decoration_loaded_natively',
        );
    }
    echo json_encode(
        [
            'status'       => 'PASS',
            'phase'        => $phase,
            'core'         => (new Typo3Version())->getVersion(),
            'composerMode' => Environment::isComposerMode(),
            'checks'       => $checks,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    ) . "\n";
} catch (Throwable $exception) {
    echo json_encode(
        [
            'status' => 'FAIL',
            'phase'  => $phase,
            'class'  => $exception::class,
            'error'  => $exception->getMessage(),
            'trace'  => $exception->getTraceAsString(),
            'checks' => $checks,
        ],
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    ) . "\n";
    exit(1);
}
