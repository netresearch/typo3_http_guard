<?php

/**
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: Netresearch DTT GmbH
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'HTTP Guard',
    'description' => 'Controlled outbound HTTP protection for qualified TYPO3 client combinations - by Netresearch',
    'category' => 'services',
    'state' => 'alpha',
    'version' => '0.1.1',
    'autoload' => [
        'psr-4' => [
            'Netresearch\NrHttpGuard\\' => 'Classes/',
            'Netresearch\HttpGuard\\' => 'Classes/HttpGuard/',
        ],
    ],
    'constraints' => [
        'depends' => ['typo3' => '13.4.36-14.3.99', 'php' => '8.2.0-8.99.99'],
        'conflicts' => [],
        'suggests' => [],
    ],
    'author' => 'Netresearch DTT GmbH',
    'author_email' => 'typo3@netresearch.de',
    'author_company' => 'Netresearch DTT GmbH',
];
