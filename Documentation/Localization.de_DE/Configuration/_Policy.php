<?php

declare (strict_types=1);
// Replace this synthetic endpoint with the approved production origin and address.
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['nr_http_guard'] = [
    'schemaVersion' => 1,
    'mode' => 'enforce',
    'resolver' => ['staticHosts' => ['erp.internal.example' => ['10.23.4.12']]],
    'tls' => ['requireVerification' => true],
    'endpoints' => [
        'erp-orders' => [
            'origin' => 'https://erp.internal.example:8443',
            'allowedCidrs' => ['10.23.4.12/32'],
            'methods' => ['GET', 'POST'],
            'redirects' => 'none',
            'allowLoopback' => false,
            'purpose' => 'Bestellabgleich mit dem internen ERP',
            'owner' => 'ERP-Team',
            'reviewAfter' => '2027-01-15',
            'expiresAt' => '2027-04-01T00:00:00Z',
        ],
    ],
];
