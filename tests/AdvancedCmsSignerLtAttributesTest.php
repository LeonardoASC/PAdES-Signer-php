<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use PHPUnit\Framework\TestCase;

final class AdvancedCmsSignerLtAttributesTest extends TestCase
{
    public function test_it_does_not_embed_lt_reference_attributes_in_baseline_cms(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new AdvancedCmsSigner(
            certificate: $certificate
        ))->signDetachedDer(
            'hello world'
        );

        $hex = strtoupper(
            bin2hex($cms)
        );

        $this->assertStringNotContainsString(
            '2A864886F70D0109100215',
            $hex
        );

        $this->assertStringNotContainsString(
            '2A864886F70D0109100216',
            $hex
        );
    }
}
