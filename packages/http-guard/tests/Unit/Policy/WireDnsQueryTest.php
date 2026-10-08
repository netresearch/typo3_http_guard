<?php

declare (strict_types=1);
namespace Netresearch\HttpGuard\Tests\Unit\Policy;

use Netresearch\HttpGuard\{WireDnsQuery, PolicyException};
use PHPUnit\Framework\TestCase;
final class WireDnsQueryTest extends TestCase
{
    public function testUdpTruncationRetriesCompleteLengthPrefixedTcp(): void
    {
        $process = proc_open(
            [PHP_BINARY, __DIR__ . '/Fixtures/DnsTruncationResponder.php'],
            [['pipe', 'r'], ['pipe', 'w'], ['pipe', 'w']],
            $pipes
        );
        self::assertIsResource($process);
        fclose($pipes[0]);
        try {
            stream_set_timeout($pipes[1], 3);
            $readyLine = fgets($pipes[1]);
            self::assertIsString($readyLine);
            $ready = json_decode($readyLine, true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('ready', $ready['status']);
            self::assertIsInt($ready['port']);
            $answer = (new WireDnsQuery(['127.0.0.1'], $ready['port'], 1))->query(
                'api.example.',
                1
            );
            self::assertTrue($answer->complete);
            self::assertSame('8.8.8.8', $answer->records[0]['ip']);
            $resultLine = fgets($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            self::assertIsString($resultLine, $stderr);
            $result = json_decode($resultLine, true, 512, JSON_THROW_ON_ERROR);
            self::assertSame('complete', $result['status']);
            self::assertTrue($result['udpTcpQueryIdentical']);
            self::assertTrue($result['lengthPrefixedResponse']);
            fclose($pipes[1]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $stderr);
        } finally {
            foreach ($pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            if (is_resource($process)) {
                proc_terminate($process);
                proc_close($process);
            }
        }
    }
    public function testNumericNameserverAndBoundedConfigurationAreMandatory(): void
    {
        foreach ([
            [],
            ['resolver.example'],
            ['127.0.0.1%eth0'],
            ['1.1.1.1', '8.8.8.8', '9.9.9.9', '4.4.4.4'],
        ] as $servers) {
            try {
                new WireDnsQuery($servers);
                self::fail('Bad resolver config accepted');
            } catch (PolicyException $e) {
                self::assertSame('configuration_invalid', $e->reasonCode());
            }
        }
    }
    public function testDnsBlackholeHasMeasuredQueryBudgetAndNoNativeFallback(): void
    {
        $udp = stream_socket_server(
            'udp://127.0.0.1:0',
            $errno,
            $error,
            STREAM_SERVER_BIND
        );
        self::assertIsResource($udp);
        $name = stream_socket_get_name($udp, false);
        $port = (int) substr($name, strrpos($name, ':') + 1);
        $started = hrtime(true);
        try {
            (new WireDnsQuery(['127.0.0.1'], $port, 0.05))->query(
                'blackhole.example.',
                1
            );
            self::fail('blackhole resolved');
        } catch (PolicyException $e) {
            self::assertSame('resolution_unverified', $e->reasonCode());
        } finally {
            fclose($udp);
        }
        $seconds = (hrtime(true) - $started) / 1000000000.0;
        self::assertGreaterThanOrEqual(0.04, $seconds);
        self::assertLessThan(0.5, $seconds);
    }
    public function testDefaultNameserverConfigurationIsOnlyNeededAtActualDnsQuery(): void
    {
        $query = new \Netresearch\HttpGuard\WireDnsQuery(
            resolvConfPath: '/definitely-nonexistent-http-guard-resolv.conf'
        );
        self::assertInstanceOf(
            \Netresearch\HttpGuard\DnsQueryInterface::class,
            $query
        );
        try {
            $query->query('safe.example.', 1);
            self::fail('Missing resolver configuration accepted');
        } catch (\Netresearch\HttpGuard\PolicyException $e) {
            self::assertSame('configuration_invalid', $e->reasonCode());
        }
    }
}
