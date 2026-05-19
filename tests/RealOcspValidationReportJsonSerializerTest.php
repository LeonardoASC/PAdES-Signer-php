<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\RealOcspValidationReportJsonSerializer;
use PHPUnit\Framework\TestCase;

final class RealOcspValidationReportJsonSerializerTest extends TestCase
{
    public function test_it_serializes_real_ocsp_report_to_json(): void
    {
        $json = (
            new RealOcspValidationReportJsonSerializer()
        )->serialize([
            'summary' => 'successful=no | status=null',
            'inspection' => [
                'successful' => false,
            ],
        ]);

        $this->assertJson(
            $json
        );

        $this->assertStringContainsString(
            '"summary": "successful=no | status=null"',
            $json
        );
    }
}