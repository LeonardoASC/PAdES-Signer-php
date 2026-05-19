<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\Validation\LtvValidationSummary;
use PHPUnit\Framework\TestCase;

final class LtvValidationSummaryTest extends TestCase
{
    public function test_it_summarizes_ltv_material(): void
    {
        $summary = (new LtvValidationSummary())
            ->summarize(
                new LtvValidationMaterial(
                    certificatesDer: ['cert-a'],
                    ocspResponsesDer: ['ocsp-a'],
                    crlsDer: ['crl-a']
                )
            );

        $this->assertStringContainsString(
            'LTV capable: yes',
            $summary
        );

        $this->assertStringContainsString(
            'Certificates: 1',
            $summary
        );

        $this->assertStringContainsString(
            'OCSP: 1',
            $summary
        );

        $this->assertStringContainsString(
            'CRLs: 1',
            $summary
        );
    }
}