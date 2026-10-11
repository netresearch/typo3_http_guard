<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace {
    if (!in_array($argv[1] ?? '', ['13', '14'], true) || !in_array($argv[2] ?? '', ['value', 'reference'], true)) {
        exit(2);
    }
    $GLOBALS['httpGuardCoreAbiMajor']     = $argv[1];
    $GLOBALS['httpGuardCoreAbiReference'] = $argv[2] === 'reference';
}

namespace TYPO3\CMS\Core\Information {
    final class Typo3Version
    {
        public function getVersion(): string
        {
            return $GLOBALS['httpGuardCoreAbiMajor'] === '14' ? '14.3.9' : '13.4.37';
        }
    }
}

namespace TYPO3\CMS\Core\Http\Client {
    class GuzzleClientFactory {}
}

namespace TYPO3\CMS\Core\Http {
    trait CompatibleConstructor
    {
        public function __construct(Client\GuzzleClientFactory $guzzleClientFactory) {}
    }
    trait ValueRequest
    {
        public function request(
            string $uri,
            string $method = 'GET',
            array $options = [],
            ?string $context = null,
        ): \Psr\Http\Message\ResponseInterface {
            throw new \LogicException('Synthetic parent is never invoked');
        }
    }
    trait ReferenceRequest
    {
        public function &request(
            string $uri,
            string $method = 'GET',
            array $options = [],
            ?string $context = null,
        ): \Psr\Http\Message\ResponseInterface {
            throw new \LogicException('Synthetic parent is never invoked');
        }
    }
    if ($GLOBALS['httpGuardCoreAbiMajor'] === '14') {
        if ($GLOBALS['httpGuardCoreAbiReference']) {
            readonly class RequestFactory
            {
                use CompatibleConstructor;
                use ReferenceRequest;
            }
        } else {
            readonly class RequestFactory
            {
                use CompatibleConstructor;
                use ValueRequest;
            }
        }
    } elseif ($GLOBALS['httpGuardCoreAbiReference']) {
        class RequestFactory
        {
            use CompatibleConstructor;
            use ReferenceRequest;
        }
    } else {
        class RequestFactory
        {
            use CompatibleConstructor;
            use ValueRequest;
        }
    }
}

namespace {
    require dirname(__DIR__) . '/bootstrap.php';
    try {
        $replacement = Netresearch\NrHttpGuard\Http\RequestFactoryCompatibility::replacementClass();
        if (!class_exists($replacement)) {
            exit(4);
        }
        echo 'SUPPORTED';
        exit(0);
    } catch (Netresearch\HttpGuard\PolicyException $failure) {
        fwrite(STDERR, $failure->reasonCode());
        exit(3);
    }
}
