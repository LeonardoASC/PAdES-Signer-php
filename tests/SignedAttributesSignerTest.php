<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesBuilder;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesSigner;
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
}