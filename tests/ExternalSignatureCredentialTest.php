<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Internal\Crypto\PadesCmsSigner;
use NihilLabs\Pades\Signing\CallbackSignerProvider;
use NihilLabs\Pades\Signing\CloudKmsSignerProvider;
use NihilLabs\Pades\Signing\ExternalSignatureCredential;
use NihilLabs\Pades\Signing\HsmSignerProvider;
use NihilLabs\Pades\Signing\OpenSslSignerProvider;
use NihilLabs\Pades\Signing\Pkcs11SignerProvider;
use NihilLabs\Pades\Signing\RemoteSignerProvider;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Signing\SmartcardSignerProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ExternalSignatureCredentialTest extends TestCase
{
    public function test_it_builds_cms_with_external_callback_signature(): void
    {
        $pfx = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $credential = ExternalSignatureCredential::remote(
            certificatePem: $pfx->getPublicCertificate(),
            keyReference: 'remote-key-1',
            certificateChainPem: $pfx->getExtraCertificates()
        );

        $provider = new CallbackSignerProvider(
            function (
                string $data,
                SignatureCredentialInterface $credential,
                SignatureAlgorithmPolicy $algorithmPolicy
            ): string {
                self::assertInstanceOf(ExternalSignatureCredential::class, $credential);

                return $algorithmPolicy->hash($data);
            }
        );

        $cms = (new PadesCmsSigner(
            certificate: $credential,
            signerProvider: $provider
        ))->signPdfByteRangeData('hello external');

        $this->assertNotEmpty($cms);
        $this->assertSame('remote-signing', $credential->getStorageType());
        $this->assertSame('remote-key-1', $credential->getKeyReference());
    }

    public function test_local_openssl_provider_rejects_external_credentials_without_private_key(): void
    {
        $pfx = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $credential = ExternalSignatureCredential::hsm(
            certificatePem: $pfx->getPublicCertificate(),
            keyReference: 'slot-1:key-1'
        );

        $this->expectException(RuntimeException::class);

        (new OpenSslSignerProvider())->sign(
            data: 'hello external',
            credential: $credential
        );
    }

    public function test_it_exposes_specific_external_signer_provider_types(): void
    {
        $callback = fn (string $data, SignatureCredentialInterface $credential, SignatureAlgorithmPolicy $policy): string => $policy->hash($data);

        $this->assertSame('hsm', (new HsmSignerProvider($callback))->getProviderType());
        $this->assertSame('pkcs11', (new Pkcs11SignerProvider($callback))->getProviderType());
        $this->assertSame('smartcard', (new SmartcardSignerProvider($callback))->getProviderType());
        $this->assertSame('cloud-kms', (new CloudKmsSignerProvider($callback))->getProviderType());
        $this->assertSame('remote-signing', (new RemoteSignerProvider($callback))->getProviderType());
    }
}
