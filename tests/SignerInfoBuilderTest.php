<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Internal\Crypto\Cades\SignedAttributesBuilder;
use NihilLabs\Pades\Internal\Crypto\Cades\SignedAttributesSigner;
use NihilLabs\Pades\Internal\Crypto\Cades\SignerInfoBuilder;
use PHPUnit\Framework\TestCase;
use NihilLabs\Pades\Crypto\Asn1\Der;

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
                signedAttributesForCms: Der::contextSpecificImplicitFromEncoded(
                    0,
                    $attributes
                ),
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
            hex2bin('300d06096086480165030402010500'),
            $signerInfo
        );

        $this->assertStringNotContainsString(
            hex2bin('300b0609608648016503040201'),
            $signerInfo
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d01010b'),
            $signerInfo
        );

        $this->assertStringNotContainsString(
            hex2bin('2a864886f70d010101'),
            $signerInfo
        );

        $this->assertStringContainsString(
            "\xA0",
            $signerInfo
        );

        $this->assertStringContainsString(
            Der::contextSpecificImplicitFromEncoded(0, $attributes),
            $signerInfo
        );

        $this->assertStringNotContainsString(
            $attributes,
            $signerInfo
        );
    }
    public function test_it_builds_signer_info_with_unsigned_attributes(): void
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

        $unsignedAttributes = \NihilLabs\Pades\Crypto\Asn1\Der::contextSpecificImplicitFromEncoded(
            1,
            \NihilLabs\Pades\Crypto\Asn1\Der::set('unsigned')
        );

        $signerInfo = (new SignerInfoBuilder())
            ->build(
                certificate: $certificate,
                signedAttributesForCms: \NihilLabs\Pades\Crypto\Asn1\Der::contextSpecificImplicitFromEncoded(
                    0,
                    $attributes
                ),
                encryptedDigest: $signature,
                unsignedAttributesForCms: $unsignedAttributes
            );

        $this->assertStringContainsString(
            'unsigned',
            $signerInfo
        );
    }
}
