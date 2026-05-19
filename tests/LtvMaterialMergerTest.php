<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvMaterialMerger;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use PHPUnit\Framework\TestCase;

final class LtvMaterialMergerTest extends TestCase
{
    public function test_it_merges_ltv_materials(): void
    {
        $a = new LtvValidationMaterial(
            certificatesDer: ['cert-a'],
            ocspResponsesDer: ['ocsp-a'],
            crlsDer: ['crl-a']
        );

        $b = new LtvValidationMaterial(
            certificatesDer: ['cert-b'],
            ocspResponsesDer: ['ocsp-b'],
            crlsDer: ['crl-b']
        );

        $merged = (new LtvMaterialMerger())
            ->merge($a, $b);

        $this->assertCount(
            2,
            $merged->certificatesDer
        );

        $this->assertCount(
            2,
            $merged->ocspResponsesDer
        );

        $this->assertCount(
            2,
            $merged->crlsDer
        );
    }

    public function test_it_removes_duplicates(): void
    {
        $a = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp'],
            crlsDer: ['crl']
        );

        $b = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp'],
            crlsDer: ['crl']
        );

        $merged = (new LtvMaterialMerger())
            ->merge($a, $b);

        $this->assertCount(
            1,
            $merged->certificatesDer
        );

        $this->assertCount(
            1,
            $merged->ocspResponsesDer
        );

        $this->assertCount(
            1,
            $merged->crlsDer
        );
    }
}