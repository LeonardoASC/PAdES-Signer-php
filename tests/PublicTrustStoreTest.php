<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\X509\OpenSslCertificateChainValidator;
use NihilLabs\Pades\PadesLtvEnricher;
use NihilLabs\Pades\PadesTrustStore;
use NihilLabs\Pades\PadesValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PublicTrustStoreTest extends TestCase
{
    public function test_public_trust_store_validates_a_certificate_chain(): void
    {
        $material = $this->certificateMaterial(ca: true);

        $trustStore = PadesTrustStore::fromPem($material['root']);

        $result = (new OpenSslCertificateChainValidator())
            ->validateCertificateChain(
                signerCertificatePem: $material['leaf'],
                candidateCertificatesPem: [],
                trustStore: $trustStore
            );

        $this->assertTrue($result->trusted, implode("\n", $result->messages));
        $this->assertSame($material['root'], $result->trustAnchorPem);
        $this->assertCount(1, $trustStore->getTrustedCertificatesPem());
        $this->assertNotNull($trustStore->credentialValidator());
    }

    public function test_public_trust_store_loads_certificates_from_files_and_directories(): void
    {
        $material = $this->certificateMaterial(ca: true);
        $file = tempnam(sys_get_temp_dir(), 'pades-trust-anchor-');
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pades-trust-store-' . bin2hex(random_bytes(4));

        $this->assertIsString($file);
        $this->assertTrue(mkdir($directory));
        $this->assertNotFalse(file_put_contents($file, $material['root']));
        $this->assertNotFalse(file_put_contents($directory . DIRECTORY_SEPARATOR . 'root.pem', $material['root']));

        $this->assertCount(1, PadesTrustStore::fromFile($file)->getTrustedCertificatesPem());
        $this->assertCount(1, PadesTrustStore::fromFiles([$file])->getTrustedCertificatesPem());
        $this->assertCount(1, PadesTrustStore::fromDirectory($directory)->getTrustedCertificatesPem());
    }

    public function test_real_lt_enrichment_rejects_untrusted_signer_chain_before_collecting_material(): void
    {
        $trusted = $this->certificateMaterial(ca: true);
        $untrusted = $this->certificateMaterial(ca: true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cadeia X.509 nao ancora');

        (new PadesLtvEnricher())->addRealLt(
            signedPdfContent: '%PDF-1.4',
            signerCertificatePem: $untrusted['leaf'],
            trustStore: PadesTrustStore::fromPem($trusted['root'])
        );
    }

    public function test_public_validator_can_be_created_with_tsa_trust_store(): void
    {
        $material = $this->certificateMaterial(ca: true);

        $validator = PadesValidator::withTsaTrustStore(
            PadesTrustStore::fromPem($material['root'])
        );

        $this->assertInstanceOf(PadesValidator::class, $validator);
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
            ['commonName' => $ca ? 'Public Trust Root CA' : 'Invalid Trust Root'],
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
