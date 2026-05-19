<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\ContentInfoBuilder;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesBuilder;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesSigner;
use NihilLabs\Pades\Crypto\Cades\SignedDataBuilder;
use NihilLabs\Pades\Crypto\Cades\SignerInfoBuilder;
use PHPUnit\Framework\TestCase;
use NihilLabs\Pades\Crypto\Asn1\Der;

final class ContentInfoBuilderTest extends TestCase
{
    public function test_it_builds_content_info(): void
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

        $signedData = (new SignedDataBuilder())
            ->build(
                certificate: $certificate,
                signerInfo: $signerInfo
            );

        $contentInfo = (new ContentInfoBuilder())
            ->build($signedData);

        $this->assertNotEmpty($contentInfo);

        $this->assertStringStartsWith(
            "\x30",
            $contentInfo
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010702'),
            $contentInfo
        );
    }
}
