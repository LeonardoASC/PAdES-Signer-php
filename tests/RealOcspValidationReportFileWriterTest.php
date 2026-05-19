<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationReportFileWriter;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationReportFileWriterTest extends TestCase
{
    public function test_it_writes_real_ocsp_report_file(): void
    {
        $path =
            sys_get_temp_dir()
            . '/real-ocsp-report.json';

        (
            new RealOcspValidationReportFileWriter()
        )->write(
            $path,
            '{"successful":false}'
        );

        $this->assertFileExists(
            $path
        );

        $this->assertSame(
            '{"successful":false}',
            file_get_contents($path)
        );
    }
}