<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Internal\Crypto\Cades\SignatureTimestampTokenAttribute;
use NihilLabs\Pades\Crypto\Timestamp\TimestampResponseParser;
use PHPUnit\Framework\TestCase;

final class SignatureTimestampTokenAttributeTest extends TestCase
{
    public function test_it_builds_signature_timestamp_token_attribute(): void
    {
        $response = file_get_contents(
            __DIR__ . '/Fixtures/timestamp-response.tsr'
        );

        $this->assertNotFalse($response);

        $token = (new TimestampResponseParser())
            ->extractToken($response);

        $attribute = (new SignatureTimestampTokenAttribute())
            ->build($token);

        $this->assertNotEmpty($attribute);

        $this->assertStringStartsWith(
            "\x30",
            $attribute
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010910020e'),
            $attribute
        );
    }
}