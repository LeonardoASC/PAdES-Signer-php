<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspResponseStatusExtractor;
use PHPUnit\Framework\TestCase;

final class OcspResponseStatusExtractorTest extends TestCase
{
    public function test_it_extracts_successful_status(): void
    {
        $response = "\x30\x03\x0A\x01\x00";

        $extractor = new OcspResponseStatusExtractor();

        $this->assertSame(
            0,
            $extractor->extract($response)
        );

        $this->assertTrue(
            $extractor->isSuccessful($response)
        );
    }

    public function test_it_rejects_invalid_response(): void
    {
        $extractor = new OcspResponseStatusExtractor();

        $this->assertNull(
            $extractor->extract('invalid')
        );
    }
}