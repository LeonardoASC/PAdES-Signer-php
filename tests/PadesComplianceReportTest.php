<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Validation\PadesComplianceReport;
use PHPUnit\Framework\TestCase;

final class PadesComplianceReportTest extends TestCase
{
    public function test_it_reports_not_ready_when_signing_certificate_v2_is_missing(): void
    {
        $report = (new PadesComplianceReport())
            ->fromInspection([
                'is_cms_signed_data' => true,
                'has_signing_certificate_v2' => false,
                'is_pades_b_b_ready' => false,
            ]);

        $this->assertSame('Not PAdES-B-B', $report['level']);
        $this->assertFalse($report['ready']);
        $this->assertTrue($report['checks']['CMS SignedData']);
        $this->assertFalse($report['checks']['SigningCertificateV2']);
    }

    public function test_it_reports_pades_b_b_when_all_checks_pass(): void
    {
        $report = (new PadesComplianceReport())
            ->fromInspection([
                'is_cms_signed_data' => true,
                'has_signing_certificate_v2' => true,
                'is_pades_b_b_ready' => true,
            ]);

        $this->assertSame('PAdES-B-B', $report['level']);
        $this->assertTrue($report['ready']);
    }

    public function test_it_reports_pades_b_t_when_signature_timestamp_token_exists(): void
    {
        $report = (new PadesComplianceReport())
            ->fromInspection([
                'is_cms_signed_data' => true,
                'has_content_type' => true,
                'has_message_digest' => true,
                'has_signing_time' => true,
                'has_signing_certificate_v2' => true,
                'has_signature_timestamp_token' => true,
                'is_pades_b_b_ready' => true,
                'is_pades_b_t_ready' => true,
            ]);

        $this->assertSame('PAdES-B-T', $report['level']);
        $this->assertTrue($report['ready']);
        $this->assertTrue($report['checks']['signatureTimeStampToken']);
    }
}
