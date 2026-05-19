<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationReportJsonSerializer;
use PHPUnit\Framework\TestCase;

final class LtvValidationReportJsonSerializerTest extends TestCase
{
    public function test_it_serializes_ltv_report_to_json(): void
    {
        $json = (
            new LtvValidationReportJsonSerializer()
        )->serialize([
            'ready' => true,
            'summary' => 'LTV capable',
        ]);

        $this->assertJson(
            $json
        );

        $this->assertStringContainsString(
            '"ready": true',
            $json
        );

        $this->assertStringContainsString(
            '"summary": "LTV capable"',
            $json
        );
    }
}