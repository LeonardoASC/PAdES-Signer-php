<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Signing\OpenSslSignerProvider;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OpenSslSignerProviderAlgorithmTest extends TestCase
{
    public function test_it_signs_with_configured_sha512_hash(): void
    {
        $credential = new PfxSignatureCredential(
            pathOrCertificate: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signature = (new OpenSslSignerProvider())->sign(
            data: 'hello world',
            credential: $credential,
            algorithmPolicy: new SignatureAlgorithmPolicy(
                hashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA512
            )
        );

        $this->assertNotEmpty($signature);
    }

    public function test_local_openssl_provider_rejects_rsa_pss_without_explicit_padding_support(): void
    {
        $credential = new PfxSignatureCredential(
            pathOrCertificate: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $this->expectException(RuntimeException::class);

        (new OpenSslSignerProvider())->sign(
            data: 'hello world',
            credential: $credential,
            algorithmPolicy: new SignatureAlgorithmPolicy(
                signatureAlgorithm: SignatureAlgorithmPolicy::SIGNATURE_RSA_PSS
            )
        );
    }
}
