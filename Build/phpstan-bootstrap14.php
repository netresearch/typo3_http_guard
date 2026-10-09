<?php

declare (strict_types=1);
namespace Netresearch\NrHttpGuard\Http;

require __DIR__ . '/../Tests/bootstrap.php';
/**
 * Static-analysis shell for the excluded, incompatible Core 13 declaration.
 * This bootstrap is never used by production or genuine Core integration tests.
 */
if ((new \ReflectionClass(\TYPO3\CMS\Core\Http\RequestFactory::class))->isReadOnly()) {
    final readonly class GuardedRequestFactory13 extends \TYPO3\CMS\Core\Http\RequestFactory
    {
        use RawRequestGuardTrait;
    }
} else {
    throw new \RuntimeException('This analysis harness requires actual Core 14.');
}
