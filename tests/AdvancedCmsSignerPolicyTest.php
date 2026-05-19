<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\Cades\IcpBrasilSignaturePolicy;
use NihilLabs\Pades\Crypto\OpenSslBinaryCmsVerifier;
use PHPUnit\Framework\TestCase;

final class AdvancedCmsSignerPolicyTest extends TestCase
{
    public function test_it_generates_cms_with_signature_policy_attribute(): void
    {
        $data = 'hello world';
        $certificate = $this->certificate();

        $cms = (new AdvancedCmsSigner(
            certificate: $certificate,
            signaturePolicy: IcpBrasilSignaturePolicy::adRtPdfPlaceholder(
                policyHash: str_repeat("\x33", 32),
                policyUri: 'https://example.test/icp-policy.der'
            )
        ))->signDetachedDer($data);

        $this->assertStringContainsString(
            hex2bin('2A864886F70D010910020F'),
            $cms
        );

        $this->assertTrue(
            (new OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $cms,
                    signedData: $data
                )
        );
    }

    public function test_it_keeps_generic_cms_without_policy_working(): void
    {
        $data = 'hello world';
        $cms = (new AdvancedCmsSigner($this->certificate()))
            ->signDetachedDer($data);

        $this->assertStringNotContainsString(
            hex2bin('2A864886F70D010910020F'),
            $cms
        );

        $this->assertTrue(
            (new OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $cms,
                    signedData: $data
                )
        );
    }

    private function certificate(): PfxCertificate
    {
        return new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );
    }
}
