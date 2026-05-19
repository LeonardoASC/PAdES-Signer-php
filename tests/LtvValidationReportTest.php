<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\Validation\LtvValidationReport;
use PHPUnit\Framework\TestCase;

final class LtvValidationReportTest extends TestCase
{
    public function test_it_generates_ltv_report(): void
    {
        $material = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp']
        );

        $report = (
            new LtvValidationReport()
        )->generate($material);

        $this->assertArrayHasKey(
            'summary',
            $report
        );

        $this->assertArrayHasKey(
            'capabilities',
            $report
        );

        $this->assertArrayHasKey(
            'ready',
            $report
        );

        $this->assertTrue(
            $report['ready']
        );
    }
}