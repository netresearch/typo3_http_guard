<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'HTTP Guard',
    'description' => 'Controlled outbound HTTP policy for verified TYPO3 Core client combinations',
    'category' => 'services',
    'state' => 'alpha',
    'version' => '0.1.0',
    'autoload' => [
        'psr-4' => [
            'Netresearch\NrHttpGuard\\' => 'Classes/',
            'Netresearch\HttpGuard\\' => 'Classes/HttpGuard/',
        ],
    ],
    'constraints' => [
        'depends' => ['typo3' => '13.4.35-14.3.7', 'php' => '8.2.0-8.5.99'],
        'conflicts' => [],
        'suggests' => [],
    ],
];
