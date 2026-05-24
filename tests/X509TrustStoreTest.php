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
        $otherCertificatePem = file_get_contents(__DIR__ . '/Fixtures/unrelated-trust-anchor.fixture');
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
        $this->assertSame(1, $result->details['chain_length']);
        $this->assertCount(1, $result->details['fingerprints']);
    }

    public function test_it_builds_alternative_paths_and_validates_ca_constraints(): void
    {
        $material = $this->certificateMaterial(ca: true);
        $unrelated = $this->certificateMaterial(ca: true);

        $result = (new OpenSslCertificateChainValidator())
            ->validateCertificateChain(
                signerCertificatePem: $material['leaf'],
                candidateCertificatesPem: [],
                trustStore: new InMemoryTrustStore([
                    $unrelated['root'],
                    $material['root'],
                ])
            );

        $this->assertTrue($result->trusted, implode("\n", $result->messages));
        $this->assertSame($material['root'], $result->trustAnchorPem);
        $this->assertSame(2, $result->details['chain_length']);
    }

    public function test_it_rejects_issuer_without_ca_basic_constraints(): void
    {
        $material = $this->certificateMaterial(ca: false);

        $result = (new OpenSslCertificateChainValidator())
            ->validateCertificateChain(
                signerCertificatePem: $material['leaf'],
                candidateCertificatesPem: [],
                trustStore: new InMemoryTrustStore([$material['root']])
            );

        $this->assertFalse($result->trusted);
        $this->assertContains(
            'trust anchor da cadeia nao possui Basic Constraints CA:TRUE.',
            $result->messages
        );
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

    /**
     * @return array{root:string,leaf:string}
     */
    private function certificateMaterial(bool $ca): array
    {
        $config = $this->opensslConfig();
        $rootKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $config,
        ]);
        $this->assertNotFalse($rootKey);

        $rootCsr = openssl_csr_new(
            ['commonName' => $ca ? 'Test Root CA' : 'Invalid Root'],
            $rootKey,
            ['config' => $config, 'digest_alg' => 'sha256']
        );
        $this->assertNotFalse($rootCsr);

        $rootCert = openssl_csr_sign(
            $rootCsr,
            null,
            $rootKey,
            365,
            ['config' => $config, 'x509_extensions' => $ca ? 'v3_ca' : 'not_ca', 'digest_alg' => 'sha256']
        );
        $this->assertNotFalse($rootCert);

        $leafKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $config,
        ]);
        $this->assertNotFalse($leafKey);

        $leafCsr = openssl_csr_new(
            ['commonName' => 'Signer'],
            $leafKey,
            ['config' => $config, 'digest_alg' => 'sha256']
        );
        $this->assertNotFalse($leafCsr);

        $leafCert = openssl_csr_sign(
            $leafCsr,
            $rootCert,
            $rootKey,
            365,
            ['config' => $config, 'x509_extensions' => 'usr_cert', 'digest_alg' => 'sha256']
        );
        $this->assertNotFalse($leafCert);

        openssl_x509_export($rootCert, $rootPem);
        openssl_x509_export($leafCert, $leafPem);

        return [
            'root' => $rootPem,
            'leaf' => $leafPem,
        ];
    }

    private function opensslConfig(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'pades-openssl-');
        $this->assertIsString($path);
        file_put_contents($path, implode("\n", [
            '[ req ]',
            'distinguished_name = dn',
            '[ dn ]',
            '[ v3_ca ]',
            'basicConstraints = critical, CA:true',
            'keyUsage = critical, keyCertSign, cRLSign',
            'subjectKeyIdentifier = hash',
            'authorityKeyIdentifier = keyid:always,issuer',
            '[ not_ca ]',
            'basicConstraints = critical, CA:false',
            'keyUsage = critical, digitalSignature',
            'subjectKeyIdentifier = hash',
            '[ usr_cert ]',
            'basicConstraints = critical, CA:false',
            'keyUsage = critical, digitalSignature, nonRepudiation',
            'extendedKeyUsage = emailProtection',
            'certificatePolicies = 1.2.3.4',
            'subjectKeyIdentifier = hash',
            'authorityKeyIdentifier = keyid,issuer',
        ]));

        return $path;
    }

}
