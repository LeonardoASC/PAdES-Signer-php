<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Internal\Crypto\Cades\SignedAttributesBuilder;
use NihilLabs\Pades\Internal\Crypto\Cades\SignedAttributesSigner;
use PHPUnit\Framework\TestCase;

final class SignedAttributesSignerTest extends TestCase
{
    public function test_it_signs_and_verifies_signed_attributes(): void
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

        $signer = new SignedAttributesSigner($certificate);

        $signature = $signer->sign($attributes);

        $this->assertNotEmpty($signature);

        $this->assertTrue(
            $signer->verify($attributes, $signature)
        );
    }

    public function test_it_signs_the_der_set_of_attributes_not_the_implicit_cms_tag(): void
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

        $cmsTaggedAttributes = Der::contextSpecificImplicitFromEncoded(
            0,
            $attributes
        );

        $signer = new SignedAttributesSigner($certificate);

        $signature = $signer->sign($attributes);

        $this->assertTrue(
            $signer->verify($attributes, $signature)
        );

        $this->assertFalse(
            $signer->verify($cmsTaggedAttributes, $signature)
        );
    }
}
