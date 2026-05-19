<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspResponseParser;
use PHPUnit\Framework\TestCase;

final class OcspResponseParserTest extends TestCase
{
    public function test_it_detects_parsable_ocsp_response(): void
    {
        $response = "\x30\x03\x02\x01\x00";

        $this->assertTrue(
            (new OcspResponseParser())
                ->isParsable($response)
        );
    }

    public function test_it_rejects_invalid_ocsp_response(): void
    {
        $this->assertFalse(
            (new OcspResponseParser())
                ->isParsable('')
        );

        $this->assertFalse(
            (new OcspResponseParser())
                ->isParsable('invalid')
        );
    }
}