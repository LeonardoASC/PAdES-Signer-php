<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspValidator;
use PHPUnit\Framework\TestCase;

final class OcspValidatorTest extends TestCase
{
    public function test_it_rejects_invalid_response(): void
    {
        $result = (new OcspValidator())
            ->validate('invalid');

        $this->assertFalse($result->successful);
        $this->assertNull($result->certificateStatus);
    }

    public function test_it_validates_good_response(): void
    {
        $basic = "\x30\x01\xA0";

        $response =
            "\x30\x14"
            . "\x0A\x01\x00"
            . hex2bin('2B0601050507300101')
            . "\x04"
            . chr(strlen($basic))
            . $basic;

        $result = (new OcspValidator())
            ->validate($response);

        $this->assertTrue($result->successful);
        $this->assertTrue($result->isGood());
    }
}