<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Timestamp\HttpTimestampClient;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use PHPUnit\Framework\TestCase;

final class HttpTimestampClientTest extends TestCase
{
    public function test_it_implements_timestamp_client_interface(): void
    {
        $client = new HttpTimestampClient(
            url: 'https://tsa.example.test'
        );

        $this->assertInstanceOf(
            TimestampClientInterface::class,
            $client
        );
    }
}