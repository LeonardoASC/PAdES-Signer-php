<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesBuilder;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesSigner;
use NihilLabs\Pades\Crypto\Cades\SignerInfoBuilder;
use PHPUnit\Framework\TestCase;

final class SignerInfoBuilderTest extends TestCase
{
    public function test_it_builds_signer_info(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $attributes = (new SignedAttributesBuilder())
            ->build(
                data: 'hello world',
                certificatePem: $certificate->getPublicCertificate()
            );

        $signature = (new SignedAttributesSigner($certificate))
            ->sign($attributes);

        $signerInfo = (new SignerInfoBuilder())
            ->build(
                certificate: $certificate,
                signedAttributes: $attributes,
                encryptedDigest: $signature
            );

        $this->assertNotEmpty($signerInfo);

        $this->assertStringStartsWith(
            "\x30",
            $signerInfo
        );

        $this->assertStringContainsString(
            hex2bin('608648016503040201'),
            $signerInfo
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010101'),
            $signerInfo
        );
    }
}