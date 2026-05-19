<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\Validation\LtvValidationProfile;
use PHPUnit\Framework\TestCase;

final class LtvValidationProfileTest extends TestCase
{
    public function test_it_detects_ltv_capable_material(): void
    {
        $material = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp']
        );

        $this->assertTrue(
            (new LtvValidationProfile())
                ->isLtvCapable($material)
        );
    }

    public function test_it_detects_non_ltv_material(): void
    {
        $material = new LtvValidationMaterial();

        $this->assertFalse(
            (new LtvValidationProfile())
                ->isLtvCapable($material)
        );
    }
}