<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationDataBuilder;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use PHPUnit\Framework\TestCase;

final class LtvValidationDataBuilderTest extends TestCase
{
    public function test_it_builds_unsigned_attributes_from_ltv_material(): void
    {
        $attributes = (new LtvValidationDataBuilder())
            ->buildUnsignedAttributes(
                new LtvValidationMaterial(
                    ocspResponsesDer: ['ocsp-response']
                )
            );

        $this->assertCount(
            2,
            $attributes
        );
    }

    public function test_it_returns_empty_attributes_without_revocation_data(): void
    {
        $attributes = (new LtvValidationDataBuilder())
            ->buildUnsignedAttributes(
                new LtvValidationMaterial()
            );

        $this->assertSame(
            [],
            $attributes
        );
    }
}