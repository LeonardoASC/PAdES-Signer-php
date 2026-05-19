<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use PHPUnit\Framework\TestCase;

final class TimestampClientInterfaceTest extends TestCase
{
    public function test_timestamp_client_contract_exists(): void
    {
        $client = new class implements TimestampClientInterface {
            public function requestToken(string $timestampRequestDer): string
            {
                return 'token';
            }
        };

        $this->assertSame(
            'token',
            $client->requestToken('request')
        );
    }
}