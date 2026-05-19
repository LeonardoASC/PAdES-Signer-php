<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Cades\SignatureTimestampTokenAttribute;
use NihilLabs\Pades\Crypto\Cades\UnsignedAttributesBuilder;
use NihilLabs\Pades\Crypto\Timestamp\TimestampResponseParser;
use PHPUnit\Framework\TestCase;

final class UnsignedAttributesBuilderTest extends TestCase
{
    public function test_it_builds_unsigned_attributes(): void
    {
        $response = file_get_contents(
            __DIR__ . '/Output/timestamp-response.tsr'
        );

        $this->assertNotFalse($response);

        $token = (new TimestampResponseParser())
            ->extractToken($response);

        $attribute = (new SignatureTimestampTokenAttribute())
            ->build($token);

        $unsignedAttributes = (new UnsignedAttributesBuilder())
            ->build([$attribute]);

        $this->assertNotEmpty(
            $unsignedAttributes
        );

        $this->assertSame(
            "\xA1",
            $unsignedAttributes[0]
        );
    }
}