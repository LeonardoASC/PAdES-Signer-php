<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\CertificateValidationContext;
use NihilLabs\Pades\Crypto\X509\CertificateValidationPolicy;
use NihilLabs\Pades\Crypto\X509\CredentialCertificateValidator;
use NihilLabs\Pades\Crypto\X509\X509CertificateValidator;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use PHPUnit\Framework\TestCase;

final class X509CertificateValidatorTest extends TestCase
{
    public function test_it_validates_signer_certificate_structure_and_current_expiration(): void
    {
        $certificate = $this->fixtureCertificate();
        $context = $this->contextAtValidTime($certificate->getPublicCertificate());

        $result = (new X509CertificateValidator())->validate(
            certificatePem: $certificate->getPublicCertificate(),
            context: $context
        );

        $this->assertTrue($result->valid);
        $this->assertSame([], $result->messages);
        $this->assertNotEmpty($result->details['serialNumberHex']);
    }

    public function test_it_rejects_invalid_signer_certificate(): void
    {
        $result = (new X509CertificateValidator())->validate('not-a-certificate');

        $this->assertFalse($result->valid);
        $this->assertSame(
            ['Certificado do signatario nao e um X.509 valido.'],
            $result->messages
        );
    }

    public function test_it_validates_required_key_usage(): void
    {
        $certificate = $this->fixtureCertificate();

        $result = (new X509CertificateValidator())->validate(
            certificatePem: $certificate->getPublicCertificate(),
            context: $this->contextAtValidTime($certificate->getPublicCertificate()),
            policy: new CertificateValidationPolicy(requireKeyUsage: true)
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'Certificado do signatario nao possui extensao key usage.',
            $result->messages
        );
    }

    public function test_it_validates_required_extended_key_usage(): void
    {
        $certificate = $this->fixtureCertificate();

        $result = (new X509CertificateValidator())->validate(
            certificatePem: $certificate->getPublicCertificate(),
            context: $this->contextAtValidTime($certificate->getPublicCertificate()),
            policy: new CertificateValidationPolicy(requireExtendedKeyUsage: true)
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'Certificado do signatario nao possui extensao extended key usage.',
            $result->messages
        );
    }

    public function test_it_validates_expiration_at_validation_time(): void
    {
        $certificatePem = $this->fixtureCertificate()->getPublicCertificate();
        $parsed = $this->parse($certificatePem);

        $result = (new X509CertificateValidator())->validate(
            certificatePem: $certificatePem,
            context: new CertificateValidationContext(
                validationTime: (new \DateTimeImmutable())->setTimestamp($parsed['validTo_time_t'] + 1)
            )
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'Certificado do signatario estava expirado no tempo de validacao.',
            $result->messages
        );
    }

    public function test_it_validates_temporal_window_by_signing_time(): void
    {
        $certificatePem = $this->fixtureCertificate()->getPublicCertificate();
        $parsed = $this->parse($certificatePem);

        $result = (new X509CertificateValidator())->validate(
            certificatePem: $certificatePem,
            context: new CertificateValidationContext(
                validationTime: (new \DateTimeImmutable())->setTimestamp($parsed['validFrom_time_t']),
                signingTime: (new \DateTimeImmutable())->setTimestamp($parsed['validFrom_time_t'] - 1)
            )
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'Certificado do signatario ainda nao era valido no tempo de signing time.',
            $result->messages
        );
    }

    public function test_it_validates_temporal_window_by_timestamp(): void
    {
        $certificatePem = $this->fixtureCertificate()->getPublicCertificate();
        $parsed = $this->parse($certificatePem);

        $result = (new X509CertificateValidator())->validate(
            certificatePem: $certificatePem,
            context: new CertificateValidationContext(
                validationTime: (new \DateTimeImmutable())->setTimestamp($parsed['validFrom_time_t']),
                timestampTime: (new \DateTimeImmutable())->setTimestamp($parsed['validTo_time_t'] + 1)
            )
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'Certificado do signatario estava expirado no tempo de timestamp.',
            $result->messages
        );
    }

    public function test_it_adapts_certificate_validation_to_public_trust_validator_contract(): void
    {
        $certificate = $this->fixtureCertificate();
        $validator = new CredentialCertificateValidator(
            context: $this->contextAtValidTime($certificate->getPublicCertificate())
        );

        $result = $validator->validateCredential(
            new PfxSignatureCredential($certificate)
        );

        $this->assertTrue($result->trusted);
        $this->assertSame([], $result->messages);
    }

    private function fixtureCertificate(): PfxCertificate
    {
        return new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );
    }

    private function contextAtValidTime(string $certificatePem): CertificateValidationContext
    {
        $parsed = $this->parse($certificatePem);

        return new CertificateValidationContext(
            validationTime: (new \DateTimeImmutable())->setTimestamp($parsed['validFrom_time_t'] + 1)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function parse(string $certificatePem): array
    {
        $parsed = openssl_x509_parse($certificatePem);

        $this->assertIsArray($parsed);

        return $parsed;
    }
}
