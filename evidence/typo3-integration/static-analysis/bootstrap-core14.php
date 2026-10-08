<?php

declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Http;

require __DIR__ . '/../../../packages/nr-http-guard/Tests/bootstrap.php';
/**
 * Static-analysis shell for the excluded incompatible Core 13 class.
 * This file is never used in production or the real Core bootstrap matrix.
 */
if ((new \ReflectionClass(\TYPO3\CMS\Core\Http\RequestFactory::class))->isReadOnly()) {
    final readonly class GuardedRequestFactory13 extends \TYPO3\CMS\Core\Http\RequestFactory
    {
        use RawRequestGuardTrait;
    }
} else {
    throw new \RuntimeException(
        'This static-analysis harness requires the Core 14 runtime.'
    );
}
