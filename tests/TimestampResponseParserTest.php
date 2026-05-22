<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Timestamp\TimestampResponseParser;
use PHPUnit\Framework\TestCase;

final class TimestampResponseParserTest extends TestCase
{
    public function test_it_extracts_timestamp_token(): void
    {
        $response = file_get_contents(
            __DIR__ . '/Fixtures/timestamp-response.tsr'
        );

        $this->assertNotFalse($response);

        $token = (new TimestampResponseParser())
            ->extractToken($response);

        $this->assertNotEmpty($token);

        $this->assertStringStartsWith(
            "\x30",
            $token
        );
    }
}