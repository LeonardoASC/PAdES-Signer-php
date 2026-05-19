<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\Validation\LtvValidationReportFactory;
use PHPUnit\Framework\TestCase;

final class LtvValidationReportFactoryTest extends TestCase
{
    public function test_it_creates_ltv_report_array(): void
    {
        $material = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp']
        );

        $report = (
            new LtvValidationReportFactory()
        )->create($material);

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
    }

    public function test_it_creates_ltv_report_json(): void
    {
        $material = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp']
        );

        $json = (
            new LtvValidationReportFactory()
        )->createJson($material);

        $this->assertJson(
            $json
        );

        $this->assertStringContainsString(
            '"ready": true',
            $json
        );
    }
}