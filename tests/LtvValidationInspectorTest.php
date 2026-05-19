<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationInspector;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use PHPUnit\Framework\TestCase;

final class LtvValidationInspectorTest extends TestCase
{
    public function test_it_inspects_ltv_material(): void
    {
        $material = new LtvValidationMaterial(
            certificatesDer: ['cert-a', 'cert-b'],
            ocspResponsesDer: ['ocsp'],
            crlsDer: ['crl']
        );

        $inspection = (
            new LtvValidationInspector()
        )->inspect($material);

        $this->assertSame(
            2,
            $inspection['certificates']
        );

        $this->assertSame(
            1,
            $inspection['ocsp_responses']
        );

        $this->assertSame(
            1,
            $inspection['crls']
        );

        $this->assertTrue(
            $inspection['ltv_capable']
        );
    }
}