<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesBuilder;
use PHPUnit\Framework\TestCase;

final class SignedAttributesBuilderTest extends TestCase
{
    public function test_it_builds_signed_attributes(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $attributes = (
            new SignedAttributesBuilder()
        )->build(
            data: 'hello world',
            certificatePem: $certificate->getPublicCertificate()
        );

        $this->assertNotEmpty(
            $attributes
        );

        $this->assertStringStartsWith(
            "\x31",
            $attributes
        );
    }

    public function test_it_contains_message_digest_oid(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $attributes = (
            new SignedAttributesBuilder()
        )->build(
            data: 'hello world',
            certificatePem: $certificate->getPublicCertificate()
        );

        $this->assertStringContainsString(
            hex2bin(
                '2a864886f70d010904'
            ),
            $attributes
        );
    }

    public function test_it_contains_signing_certificate_v2_oid(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $attributes = (
            new SignedAttributesBuilder()
        )->build(
            data: 'hello world',
            certificatePem: $certificate->getPublicCertificate()
        );

        $this->assertStringContainsString(
            hex2bin(
                '2a864886f70d010910022f'
            ),
            $attributes
        );
    }

    public function test_it_contains_content_type_oid(): void
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

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010903'),
            $attributes
        );
    }

    public function test_it_contains_signing_time_oid(): void
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

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010905'),
            $attributes
        );
    }

    public function test_it_der_sorts_signed_attributes(): void
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

        $contentTypePosition = strpos($attributes, hex2bin('2a864886f70d010903'));
        $signingTimePosition = strpos($attributes, hex2bin('2a864886f70d010905'));
        $messageDigestPosition = strpos($attributes, hex2bin('2a864886f70d010904'));
        $signingCertificatePosition = strpos($attributes, hex2bin('2a864886f70d010910022f'));

        $this->assertNotFalse($contentTypePosition);
        $this->assertNotFalse($signingTimePosition);
        $this->assertNotFalse($messageDigestPosition);
        $this->assertNotFalse($signingCertificatePosition);

        $this->assertLessThan($signingTimePosition, $contentTypePosition);
        $this->assertLessThan($messageDigestPosition, $signingTimePosition);
        $this->assertLessThan($signingCertificatePosition, $messageDigestPosition);
    }
}
