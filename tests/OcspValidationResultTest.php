<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspValidationResult;
use PHPUnit\Framework\TestCase;

final class OcspValidationResultTest extends TestCase
{
    public function test_it_represents_good_status(): void
    {
        $result = new OcspValidationResult(
            successful: true,
            certificateStatus: 'good'
        );

        $this->assertTrue($result->isGood());
        $this->assertFalse($result->isRevoked());
        $this->assertFalse($result->isUnknown());
    }

    public function test_it_represents_revoked_status(): void
    {
        $result = new OcspValidationResult(
            successful: true,
            certificateStatus: 'revoked'
        );

        $this->assertFalse($result->isGood());
        $this->assertTrue($result->isRevoked());
    }

    public function test_it_represents_unknown_status(): void
    {
        $result = new OcspValidationResult(
            successful: true,
            certificateStatus: 'unknown'
        );

        $this->assertFalse($result->isGood());
        $this->assertTrue($result->isUnknown());
    }
}