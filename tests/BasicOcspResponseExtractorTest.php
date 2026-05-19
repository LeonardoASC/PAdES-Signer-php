<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\BasicOcspResponseExtractor;
use PHPUnit\Framework\TestCase;

final class BasicOcspResponseExtractorTest extends TestCase
{
    public function test_it_extracts_basic_ocsp_response(): void
    {
        $basic = 'basic-response';

        $response =
            hex2bin('2B0601050507300101')
            . "\x04"
            . chr(strlen($basic))
            . $basic;

        $extracted = (new BasicOcspResponseExtractor())
            ->extract($response);

        $this->assertSame(
            $basic,
            $extracted
        );
    }

    public function test_it_returns_null_when_response_is_invalid(): void
    {
        $this->assertNull(
            (new BasicOcspResponseExtractor())
                ->extract('invalid')
        );
    }
}