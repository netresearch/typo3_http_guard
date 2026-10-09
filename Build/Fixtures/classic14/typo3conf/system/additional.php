<?php

declare(strict_types=1);
require_once dirname(__DIR__, 2) . '/support/RecordingMiddleware.php';
$GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = [
    'project/A' => new Netresearch\NrHttpGuard\Tests\Fixtures\RecordingMiddleware('A'),
    'project/B' => new Netresearch\NrHttpGuard\Tests\Fixtures\RecordingMiddleware('B'),
];
$case = getenv('HTTP_GUARD_BOOT_TEST') ?: 'normal';
if ($case === 'object') {
    $GLOBALS['TYPO3_CONF_VARS']['HTTP']['handler'] = GuzzleHttp\HandlerStack::create();
}
if ($case === 'invalid-mode') {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']['mode'] = 'invalid';
}
if ($case === 'invalid-schema') {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']['schemaVersion'] = 99;
}
if ($case === 'observe' || $case === 'disabled') {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']['mode'] = $case;
}
if ($case === 'redirect-zero') {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']['redirects'] = ['max' => 0];
}

if ($case === 'review-overdue') {
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard']['endpoints']['erp-orders']['reviewAfter'] = '2020-01-01';
}

if ($case === 'factory-conflict') {
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][TYPO3\CMS\Core\Http\RequestFactory::class] = ['className' => 'Synthetic\ConflictingFactory'];
}
