<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Signing\OpenSslSignerProvider;
use NihilLabs\Pades\Signing\PemSignatureCredential;
use PHPUnit\Framework\TestCase;

final class PemSignatureCredentialTest extends TestCase
{
    public function test_it_signs_with_pem_certificate_and_private_key(): void
    {
        $content = file_get_contents(__DIR__ . '/Fixtures/certificate.pfx');
        $this->assertNotFalse($content);

        $certificates = [];
        $this->assertTrue(
            openssl_pkcs12_read($content, $certificates, '123456')
        );
        $this->assertIsString($certificates['cert']);
        $this->assertIsString($certificates['pkey']);

        $credential = new PemSignatureCredential(
            certificatePem: $certificates['cert'],
            privateKeyPemOrKey: $certificates['pkey']
        );

        $signature = (new OpenSslSignerProvider())->sign(
            data: 'hello pem',
            credential: $credential
        );

        $this->assertNotEmpty($signature);
        $this->assertSame(
            1,
            openssl_verify(
                data: 'hello pem',
                signature: $signature,
                public_key: $credential->getCertificatePem(),
                algorithm: OPENSSL_ALGO_SHA256
            )
        );
    }

    public function test_it_keeps_signer_certificate_first_in_pem_chain(): void
    {
        $pfx = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $credential = new PemSignatureCredential(
            certificatePem: $pfx->getPublicCertificate(),
            privateKeyPemOrKey: $pfx->getPrivateKey(),
            certificateChainPem: $pfx->getExtraCertificates()
        );

        $chain = $credential->getCertificateChainPem();

        $this->assertSame($credential->getCertificatePem(), $chain[0]);
    }
}
