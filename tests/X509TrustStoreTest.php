<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\InMemoryTrustStore;
use NihilLabs\Pades\Crypto\X509\OpenSslCertificateChainValidator;
use NihilLabs\Pades\Crypto\X509\TrustStoreCredentialValidator;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use PHPUnit\Framework\TestCase;

final class X509TrustStoreTest extends TestCase
{
    public function test_it_validates_a_credential_against_a_pluggable_trust_store(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $credential = new PfxSignatureCredential($certificate);
        $trustStore = new InMemoryTrustStore([
            $certificate->getPublicCertificate(),
        ]);

        $result = (new OpenSslCertificateChainValidator())
            ->validateCredential($credential, $trustStore);

        $this->assertTrue($result->trusted);
        $this->assertSame($certificate->getPublicCertificate(), $result->trustAnchorPem);
        $this->assertNotEmpty($result->chainPem);
    }

    public function test_it_supports_multiple_trust_anchors(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );
        $otherCertificatePem = file_get_contents(__DIR__ . '/Output/openssl-ref-cert.pem');
        $this->assertNotFalse($otherCertificatePem);

        $trustStore = new InMemoryTrustStore([
            $otherCertificatePem,
            $certificate->getPublicCertificate(),
        ]);

        $result = (new OpenSslCertificateChainValidator())
            ->validateCredential(
                credential: new PfxSignatureCredential($certificate),
                trustStore: $trustStore
            );

        $this->assertTrue($result->trusted);
        $this->assertSame($certificate->getPublicCertificate(), $result->trustAnchorPem);
    }

    public function test_it_adapts_chain_validation_to_public_trust_validator_contract(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $validator = new TrustStoreCredentialValidator(
            trustStore: new InMemoryTrustStore([
                $certificate->getPublicCertificate(),
            ])
        );

        $result = $validator->validateCredential(
            new PfxSignatureCredential($certificate)
        );

        $this->assertTrue($result->trusted);
        $this->assertSame([], $result->messages);
    }
}
