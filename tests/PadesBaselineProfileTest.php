<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Validation\PadesBaselineProfile;
use PHPUnit\Framework\TestCase;

final class PadesBaselineProfileTest extends TestCase
{
    public function test_it_declares_formal_pades_baseline_profiles(): void
    {
        $profiles = (new PadesBaselineProfile())->all();

        $this->assertSame(
            ['PAdES-B-B', 'PAdES-B-T', 'PAdES-B-LT', 'PAdES-B-LTA'],
            $profiles
        );
    }

    public function test_it_declares_pades_b_b_requirements(): void
    {
        $requirements = (new PadesBaselineProfile())
            ->requirements(PadesBaselineProfile::B_B);

        $this->assertContains('pdf_signature_dictionary', $requirements);
        $this->assertContains('etsi_cades_detached_subfilter', $requirements);
        $this->assertContains('valid_byte_range', $requirements);
        $this->assertContains('cms_signed_data', $requirements);
        $this->assertContains('content_type_attribute', $requirements);
        $this->assertContains('message_digest_attribute', $requirements);
        $this->assertContains('signing_certificate_v2_attribute', $requirements);
    }
}
