<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\ValidationMaterial;
use PHPUnit\Framework\TestCase;

final class ValidationMaterialTest extends TestCase
{
    public function test_it_represents_empty_validation_material(): void
    {
        $material = new ValidationMaterial();

        $this->assertTrue(
            $material->isEmpty()
        );
    }

    public function test_it_represents_non_empty_validation_material(): void
    {
        $material = new ValidationMaterial(
            certificatesDer: ['certificate'],
            ocspResponsesDer: ['ocsp'],
            crlsDer: ['crl']
        );

        $this->assertFalse(
            $material->isEmpty()
        );
    }
}