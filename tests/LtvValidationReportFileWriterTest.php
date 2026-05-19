<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationReportFileWriter;
use PHPUnit\Framework\TestCase;

final class LtvValidationReportFileWriterTest extends TestCase
{
    public function test_it_writes_ltv_report_file(): void
    {
        $path =
            sys_get_temp_dir()
            . '/ltv-report.json';

        (new LtvValidationReportFileWriter())
            ->write(
                $path,
                '{"ready":true}'
            );

        $this->assertFileExists(
            $path
        );

        $this->assertSame(
            '{"ready":true}',
            file_get_contents($path)
        );
    }
}