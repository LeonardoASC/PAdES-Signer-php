<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\CertStatusExtractor;
use PHPUnit\Framework\TestCase;

final class CertStatusExtractorTest extends TestCase
{
    public function test_it_detects_good_status(): void
    {
        $extractor = new CertStatusExtractor();

        $this->assertSame(
            'good',
            $extractor->extract("\x30\x01\xA0")
        );

        $this->assertTrue(
            $extractor->isGood("\x30\x01\xA0")
        );
    }

    public function test_it_detects_revoked_status(): void
    {
        $extractor = new CertStatusExtractor();

        $this->assertSame(
            'revoked',
            $extractor->extract("\x30\x01\xA1")
        );
    }

    public function test_it_detects_unknown_status(): void
    {
        $extractor = new CertStatusExtractor();

        $this->assertSame(
            'unknown',
            $extractor->extract("\x30\x01\xA2")
        );
    }

    public function test_it_returns_null_for_invalid_response(): void
    {
        $extractor = new CertStatusExtractor();

        $this->assertNull(
            $extractor->extract('invalid')
        );
    }
}