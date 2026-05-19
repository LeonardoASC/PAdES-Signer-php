<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\ValidationDataEmbedder;
use PHPUnit\Framework\TestCase;

final class ValidationDataEmbedderTest extends TestCase
{
    public function test_it_builds_validation_unsigned_attributes(): void
    {
        $attributes = (new ValidationDataEmbedder())
            ->buildUnsignedAttributes([
                'ocsp-response',
            ]);

        $this->assertCount(
            2,
            $attributes
        );

        $combined = implode(
            '',
            $attributes
        );

        $hex = strtoupper(
            bin2hex($combined)
        );

        $this->assertStringContainsString(
            '2A864886F70D0109100204',
            $hex
        );

        $this->assertStringContainsString(
            '2A864886F70D0109100224',
            $hex
        );
    }
}