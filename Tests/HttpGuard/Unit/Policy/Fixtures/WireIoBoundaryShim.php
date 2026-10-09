<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);

namespace Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures;

final class WireIoBoundaryState
{
    public static string $fault      = '';
    public static int $opened        = 0;
    public static int $closed        = 0;
    public static int $writes        = 0;
    public static int $reads         = 0;
    public static bool $tcp          = false;
    public static string $query      = '';
    public static string $received   = '';
    public static int $offset        = 0;
    public static array $authorities = [];

    public static function packet(bool $truncated): string
    {
        if (self::$fault === 'record-limit') {
            $id = unpack('nid', substr(self::$query, 0, 2))['id'];

            return pack('nnnnnn', $id, 0x8180, 1, 4097, 0, 0) . substr(self::$query, 12);
        }
        $id = unpack('nid', substr(self::$query, 0, 2))['id'];

        return pack('nnnnnn', $id, $truncated ? 0x8380 : 0x8180, 1, $truncated ? 0 : 1, 0, 0) . substr(self::$query, 12) . ($truncated ? '' : pack('nnnNn', 0xC00C, 1, 1, 5, 4) . chr(203) . chr(0) . chr(115) . chr(7));
    }

    public static function warning(): false
    {
        return \file_get_contents('/definitely-nonexistent-http-guard-wire-boundary-control');
    }
    public static array $blocking  = [];
    public static array $readiness = [];
    public static array $events    = [];
    public static array $timeouts  = [];
}

namespace Netresearch\HttpGuard;

use Netresearch\HttpGuard\Tests\Unit\Policy\Fixtures\WireIoBoundaryState as State;

function stream_socket_client(
    string $address,
    ?int &$errno = null,
    ?string &$error = null,
    ?float $timeout = null,
    int $flags = STREAM_CLIENT_CONNECT,
) {
    State::$timeouts[] = $timeout;
    ++State::$opened;
    State::$authorities[] = $address;
    if (State::$fault === 'socket-warning') {
        return State::warning();
    }
    if (State::$fault === 'socket' || State::$fault === 'first-socket' && State::$opened === 1) {
        return false;
    }
    State::$tcp      = str_starts_with($address, 'tcp:');
    State::$query    = '';
    State::$received = '';
    State::$offset   = 0;

    return \fopen('php://temp', 'w+b');
}
function stream_set_blocking($stream, bool $enable): bool
{
    State::$blocking[] = $enable;

    return State::$fault !== 'nonblocking';
}
function stream_select(
    ?array &$read,
    ?array &$write,
    ?array &$except,
    ?int $seconds,
    ?int $microseconds = null,
): int|false {
    State::$readiness[] = [$seconds, $microseconds];
    State::$events[]    = $read !== [] ? 'ready-read' : 'ready-write';

    return match (State::$fault) {
        'select-false'   => false,
        'select-zero'    => 0,
        'select-two'     => 2,
        'select-warning' => State::warning(),
        default          => 1,
    };
}
function fwrite($stream, string $data, ?int $length = null): int|false
{
    State::$events[] = 'write';
    ++State::$writes;
    if (State::$fault === 'write-warning') {
        return State::warning();
    }
    if (State::$fault === 'write-false') {
        return false;
    }
    if (State::$fault === 'write-zero') {
        return 0;
    }
    $count = State::$fault === 'write-partial' || State::$tcp && State::$fault === 'tcp-fragments' ? 1 : strlen($data);
    State::$query .= substr($data, 0, $count);

    return $count;
}
function fread($stream, int $length): string|false
{
    State::$events[] = 'read';
    ++State::$reads;
    if (State::$fault === 'read-warning') {
        return State::warning();
    }
    if (State::$fault === 'read-false') {
        return false;
    }
    if (State::$fault === 'read-empty') {
        return '';
    }
    if (!State::$tcp) {
        return State::packet(str_starts_with(State::$fault, 'tcp-'));
    }
    if (State::$received === '') {
        State::$query    = substr(State::$query, 2);
        $packet          = State::packet(State::$fault === 'tcp-still-truncated');
        State::$received = match (State::$fault) {
            'tcp-prefix-short' => chr(0),
            'tcp-length-small' => pack('n', 11) . str_repeat('x', 11),
            'tcp-body-short'   => pack('n', strlen($packet)) . substr($packet, 0, -1),
            default            => pack('n', strlen($packet)) . $packet,
        };
    }
    $count  = State::$fault === 'tcp-fragments' ? min(1, $length) : $length;
    $result = substr(State::$received, State::$offset, $count);
    State::$offset += strlen($result);

    return $result;
}
function fclose($stream): bool
{
    ++State::$closed;

    return \fclose($stream);
}
