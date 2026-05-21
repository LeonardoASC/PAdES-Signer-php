<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Internal\Crypto\PadesCmsSigner;
use NihilLabs\Pades\Signing\SignerProviderInterface;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use PHPUnit\Framework\TestCase;

final class PadesCmsSignerTest extends TestCase
{
    public function test_it_exposes_certificate(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signer = new PadesCmsSigner($certificate);

        $this->assertSame(
            $certificate,
            $signer->getCertificate()
        );
    }

    public function test_it_generates_advanced_cms_der(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new PadesCmsSigner($certificate))
            ->signPdfByteRangeData('hello world');

        $this->assertNotEmpty($cms);

        $this->assertStringStartsWith(
            "\x30",
            $cms
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010702'),
            $cms
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010910022f'),
            $cms
        );
    }

    public function test_advanced_cms_is_pades_b_b_ready_by_internal_inspection(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new PadesCmsSigner($certificate))
            ->signPdfByteRangeData('hello world');

        $inspection = (new \NihilLabs\Pades\Validation\PadesBaselineInspector())
            ->inspect($cms);

        $this->assertTrue(
            $inspection['is_cms_signed_data']
        );

        $this->assertTrue(
            $inspection['has_signing_certificate_v2']
        );

        $this->assertTrue(
            $inspection['is_pades_b_b_ready']
        );
    }

    public function test_it_generates_cms_with_configured_sha384_algorithm(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new PadesCmsSigner(
            certificate: $certificate,
            algorithmPolicy: new SignatureAlgorithmPolicy(
                hashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA384
            )
        ))->signPdfByteRangeData('hello world');

        $this->assertStringContainsString(hex2bin('608648016503040202'), $cms);
        $this->assertStringContainsString(hex2bin('2a864886f70d01010c'), $cms);
    }

    public function test_it_generates_cms_with_rsa_pss_algorithm_identifier_when_provider_supports_it(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new PadesCmsSigner(
            certificate: $certificate,
            signerProvider: $this->deterministicProvider(),
            algorithmPolicy: new SignatureAlgorithmPolicy(
                signatureAlgorithm: SignatureAlgorithmPolicy::SIGNATURE_RSA_PSS
            )
        ))->signPdfByteRangeData('hello world');

        $this->assertStringContainsString(hex2bin('2a864886f70d01010a'), $cms);
        $this->assertStringContainsString(hex2bin('2a864886f70d010108'), $cms);
    }

    public function test_it_generates_cms_with_ecdsa_algorithm_identifier_when_provider_supports_it(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new PadesCmsSigner(
            certificate: $certificate,
            signerProvider: $this->deterministicProvider(),
            algorithmPolicy: new SignatureAlgorithmPolicy(
                signatureAlgorithm: SignatureAlgorithmPolicy::SIGNATURE_ECDSA
            )
        ))->signPdfByteRangeData('hello world');

        $this->assertStringContainsString(hex2bin('2a8648ce3d040302'), $cms);
    }

    private function deterministicProvider(): SignerProviderInterface
    {
        return new class implements SignerProviderInterface {
            public function sign(
                string $data,
                SignatureCredentialInterface $credential,
                SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy()
            ): string {
                return hash($algorithmPolicy->hashAlgorithm, $data, binary: true);
            }
        };
    }
}
