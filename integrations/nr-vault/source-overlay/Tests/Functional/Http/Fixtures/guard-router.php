<?php

/*
 * This file is part of the nr-vault TYPO3 extension.
 * SPDX-License-Identifier: GPL-2.0-or-later
 */
declare(strict_types=1);
while (ob_get_level() > 0) {
    ob_end_flush();
}

ob_implicit_flush(true);
$directory = getenv('VAULT_GUARD_HITS');
if (!is_string($directory) || !is_dir($directory)) {
    http_response_code(500);

    return;
}

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$case = $_GET['case'] ?? 'unknown';
if (!is_string($case) || preg_match('/^[a-z0-9-]+$/D', $case) !== 1) {
    http_response_code(400);

    return;
}

$body = json_decode((string) file_get_contents('php://input'), true);
$entry = [
    'method' => $_SERVER['REQUEST_METHOD'] ?? '',
    'host' => explode(':', $_SERVER['HTTP_HOST'] ?? '')[0],
    'path' => $path,
    'authorization' => isset($_SERVER['HTTP_AUTHORIZATION']),
    'chunks' => 0,
    'api_key_header' => isset($_SERVER['HTTP_X_API_KEY']),
    'custom_header' => isset($_SERVER['HTTP_X_FIXTURE_KEY']),
    'query_secret' => isset($_GET['api_key']),
    'body_secret' => is_array($body) && isset($body['api_key']),
];
$file = $directory . '/' . $case . '-' . bin2hex(random_bytes(8)) . '.json';
// Case passed the anchored ASCII regex above; the parent owns this random 0700 directory.
// nosemgrep: php.lang.security.injection.tainted-filename.tainted-filename
file_put_contents($file, json_encode($entry, JSON_THROW_ON_ERROR));
if ($path === '/token') {
    header('Content-Type: application/json');
    echo json_encode(
        [
            'access_token' => 'fixture-access-token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
        ],
        JSON_THROW_ON_ERROR,
    );

    return;
}

if ($path === '/token-chunks') {
    header('Content-Type: application/json');
    echo '{"access_token":"';
    for ($i = 1; $i <= 30; ++$i) {
        echo 'x';
        flush();
        $entry['chunks'] = $i;
        // Reuse the validated case file in the owned 0700 fixture directory.
        // nosemgrep: php.lang.security.injection.tainted-filename.tainted-filename
        file_put_contents($file, json_encode($entry, JSON_THROW_ON_ERROR));
        if (connection_aborted() !== 0) {
            break;
        }

        usleep(100000);
    }

    echo '"}';

    return;
}

if ($path === '/short') {
    header('Content-Type: text/plain');
    header('Content-Length: 100');
    echo 'short';
    flush();

    return;
}

if ($path === '/chunks') {
    header('Content-Type: text/plain');
    header('X-Accel-Buffering: no');
    for ($i = 1; $i <= 30; ++$i) {
        echo 'chunk ' . $i . "\n";
        flush();
        $entry['chunks'] = $i;
        // Reuse the validated case file in the owned 0700 fixture directory.
        // nosemgrep: php.lang.security.injection.tainted-filename.tainted-filename
        file_put_contents($file, json_encode($entry, JSON_THROW_ON_ERROR));
        if (connection_aborted() !== 0) {
            break;
        }

        usleep(100000);
    }

    return;
}

header('Content-Type: application/json');
echo '{"ok":true}';
