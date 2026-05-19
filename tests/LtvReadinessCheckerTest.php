<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvReadinessChecker;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use PHPUnit\Framework\TestCase;

final class LtvReadinessCheckerTest extends TestCase
{
    public function test_it_detects_ready_material(): void
    {
        $material = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp']
        );

        $this->assertTrue(
            (new LtvReadinessChecker())
                ->isReady($material)
        );
    }

    public function test_it_detects_non_ready_material(): void
    {
        $this->assertFalse(
            (new LtvReadinessChecker())
                ->isReady(
                    new LtvValidationMaterial()
                )
        );
    }
}