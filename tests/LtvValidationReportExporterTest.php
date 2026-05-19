<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\Validation\LtvValidationReportExporter;
use PHPUnit\Framework\TestCase;

final class LtvValidationReportExporterTest extends TestCase
{
    public function test_it_exports_ltv_report_to_file(): void
    {
        $path =
            sys_get_temp_dir()
            . '/ltv-export-report.json';

        $material = new LtvValidationMaterial(
            certificatesDer: ['cert'],
            ocspResponsesDer: ['ocsp']
        );

        (new LtvValidationReportExporter())
            ->export(
                $path,
                $material
            );

        $this->assertFileExists(
            $path
        );

        $content = file_get_contents(
            $path
        );

        $this->assertNotFalse(
            $content
        );

        $this->assertStringContainsString(
            '"ready": true',
            $content
        );
    }
}