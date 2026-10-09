<?php

/*
 * SPDX-License-Identifier: GPL-2.0-or-later
 * SPDX-FileCopyrightText: 2026 Netresearch DTT GmbH
 */

declare(strict_types=1);
$udp = stream_socket_server('udp://127.0.0.1:0', $errno, $error, STREAM_SERVER_BIND);
if (!is_resource($udp)) {
    exit(10);
}
$name = stream_socket_get_name($udp, false);
$port = (int) substr($name, strrpos($name, ':') + 1);
$tcp  = stream_socket_server('tcp://127.0.0.1:' . $port, $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN);
if (!is_resource($tcp)) {
    exit(11);
}
echo json_encode(['status' => 'ready', 'port' => $port], JSON_THROW_ON_ERROR) . "\n";
flush();
stream_set_timeout($udp, 3);
$query = stream_socket_recvfrom($udp, 65535, 0, $peer);
if (!is_string($query) || strlen($query) < 12) {
    exit(12);
}
$id        = unpack('nid', $query)['id'];
$question  = substr($query, 12);
$truncated = pack('nnnnnn', $id, 0x8380, 1, 0, 0, 0) . $question;
if (stream_socket_sendto($udp, $truncated, 0, $peer) !== strlen($truncated)) {
    exit(13);
}
$socket = stream_socket_accept($tcp, 3);
if ($socket === false) {
    exit(14);
}
stream_set_timeout($socket, 3);
$length = fread($socket, 2);
if (!is_string($length) || strlen($length) !== 2) {
    exit(15);
}
$size     = unpack('nlength', $length)['length'];
$tcpQuery = '';
while (strlen($tcpQuery) < $size) {
    $chunk = fread($socket, $size - strlen($tcpQuery));
    if ($chunk === '' || $chunk === false) {
        exit(16);
    }
    $tcpQuery .= $chunk;
}
if ($tcpQuery !== $query) {
    exit(17);
}
$answer = pack('nnnnnn', $id, 0x8180, 1, 1, 0, 0) . $question . chr(192) . chr(12) . pack('nnNn', 1, 1, 60, 4) . inet_pton('8.8.8.8');
$framed = pack('n', strlen($answer)) . $answer;
if (fwrite($socket, $framed) !== strlen($framed)) {
    exit(18);
}
fclose($socket);
fclose($udp);
fclose($tcp);
echo json_encode(
    ['status' => 'complete', 'udpTcpQueryIdentical' => true, 'lengthPrefixedResponse' => true],
    JSON_THROW_ON_ERROR,
) . "\n";
exit(0);
