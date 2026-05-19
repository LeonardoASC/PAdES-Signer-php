<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvCapabilityDetector;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use PHPUnit\Framework\TestCase;

final class LtvCapabilityDetectorTest extends TestCase
{
    public function test_it_detects_ltv_capabilities(): void
    {
        $material = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp']
        );

        $capabilities = (
            new LtvCapabilityDetector()
        )->detect($material);

        $this->assertTrue(
            $capabilities['has_certificates']
        );

        $this->assertTrue(
            $capabilities['has_ocsp']
        );

        $this->assertFalse(
            $capabilities['has_crls']
        );

        $this->assertTrue(
            $capabilities['ltv_capable']
        );
    }

    public function test_it_detects_empty_material(): void
    {
        $capabilities = (
            new LtvCapabilityDetector()
        )->detect(
            new LtvValidationMaterial()
        );

        $this->assertFalse(
            $capabilities['has_certificates']
        );

        $this->assertFalse(
            $capabilities['has_ocsp']
        );

        $this->assertFalse(
            $capabilities['has_crls']
        );

        $this->assertFalse(
            $capabilities['ltv_capable']
        );
    }
}