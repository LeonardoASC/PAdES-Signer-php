<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\Crl\CrlParser;
use NihilLabs\Pades\Crypto\Crl\CrlValidator;
use NihilLabs\Pades\Crypto\Ocsp\OcspResponseParser;
use NihilLabs\Pades\Crypto\Ocsp\OcspResponseValidator;
use NihilLabs\Pades\Crypto\X509\CertificateFormatNormalizer;
use NihilLabs\Pades\Crypto\X509\RevocationMaterial;
use NihilLabs\Pades\Crypto\X509\RevocationMaterialValidator;
use NihilLabs\Pades\Crypto\X509\X509CertificateInfoExtractor;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;
use NihilLabs\Pades\Crypto\X509\X509PublicKeyDerExtractor;
use PHPUnit\Framework\TestCase;

final class OcspCrlValidationTest extends TestCase
{
    public function test_it_parses_and_validates_a_real_ocsp_response_structure(): void
    {
        $material = $this->certificateMaterial();
        $nonce = random_bytes(16);
        $ocsp = $this->ocspResponseDer($material, $nonce);

        $parsed = (new OcspResponseParser())->parse($ocsp);
        $this->assertSame(0, $parsed->responseStatus);
        $this->assertSame('good', $parsed->responses[0]->certificateStatus);
        $this->assertSame($nonce, $parsed->nonce);
        $this->assertNotEmpty($parsed->certificatesDer);

        $result = (new OcspResponseValidator())->validate(
            responseDer: $ocsp,
            certificatePem: $material['leafPem'],
            issuerCertificatePem: $material['rootPem'],
            expectedNonce: $nonce,
            validationTime: new \DateTimeImmutable('2026-05-24 12:00:00', new \DateTimeZone('UTC'))
        );

        $this->assertTrue($result->successful);
        $this->assertSame('good', $result->certificateStatus);
    }

    public function test_it_rejects_ocsp_when_nonce_or_status_is_not_acceptable(): void
    {
        $material = $this->certificateMaterial();
        $ocsp = $this->ocspResponseDer($material, 'expected-nonce', 'revoked');

        $wrongNonce = (new OcspResponseValidator())->validate(
            responseDer: $ocsp,
            certificatePem: $material['leafPem'],
            issuerCertificatePem: $material['rootPem'],
            expectedNonce: 'wrong-nonce',
            validationTime: new \DateTimeImmutable('2026-05-24 12:00:00', new \DateTimeZone('UTC'))
        );

        $this->assertFalse($wrongNonce->successful);
        $this->assertSame('revoked', $wrongNonce->certificateStatus);
    }

    public function test_it_parses_and_validates_crl_material(): void
    {
        $material = $this->certificateMaterial();
        $crl = $this->crlDer($material);

        $parsed = (new CrlParser())->parse($crl);
        $this->assertSame(
            (new X509NameDerExtractor())->extractSubjectNameDer($material['rootPem']),
            $parsed->issuerNameDer
        );
        $this->assertSame([], $parsed->revokedSerialNumbersHex);

        $this->assertTrue((new CrlValidator())->validate(
            crlDer: $crl,
            issuerCertificatePem: $material['rootPem'],
            certificatePem: $material['leafPem'],
            validationTime: new \DateTimeImmutable('2026-05-24 12:00:00', new \DateTimeZone('UTC'))
        ));

        $revokedCrl = $this->crlDer($material, [$material['leafSerialHex']]);
        $this->assertFalse((new CrlValidator())->validate(
            crlDer: $revokedCrl,
            issuerCertificatePem: $material['rootPem'],
            certificatePem: $material['leafPem'],
            validationTime: new \DateTimeImmutable('2026-05-24 12:00:00', new \DateTimeZone('UTC'))
        ));
    }

    public function test_it_integrates_ocsp_and_crl_with_chain_revocation_validation(): void
    {
        $material = $this->certificateMaterial();
        $revocation = new RevocationMaterial(
            ocspResponsesDer: [$this->ocspResponseDer($material)],
            crlsDer: [$this->crlDer($material)]
        );

        $result = (new RevocationMaterialValidator())->validate(
            chainPem: [$material['leafPem'], $material['rootPem']],
            material: $revocation,
            validationTime: new \DateTimeImmutable('2026-05-24 12:00:00', new \DateTimeZone('UTC'))
        );

        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertTrue($result->checks['ocsp_valid']);
        $this->assertTrue($result->checks['crl_valid']);
        $this->assertTrue($result->checks['has_usable_revocation_data']);
    }

    /**
     * @return array{
     *     rootPem:string,
     *     rootKey:\OpenSSLAsymmetricKey,
     *     leafPem:string,
     *     leafSerialHex:string
     * }
     */
    private function certificateMaterial(): array
    {
        $config = $this->opensslConfig();
        $rootKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $config,
        ]);
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $rootKey);

        $rootCsr = openssl_csr_new(
            ['commonName' => 'PAdES Test Root'],
            $rootKey,
            ['config' => $config, 'digest_alg' => 'sha256']
        );
        $this->assertNotFalse($rootCsr);

        $rootCert = openssl_csr_sign(
            $rootCsr,
            null,
            $rootKey,
            3650,
            ['config' => $config, 'x509_extensions' => 'v3_ca', 'digest_alg' => 'sha256'],
            0x1001
        );
        $this->assertNotFalse($rootCert);

        $leafKey = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'config' => $config,
        ]);
        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $leafKey);

        $leafCsr = openssl_csr_new(
            ['commonName' => 'PAdES Test Signer'],
            $leafKey,
            ['config' => $config, 'digest_alg' => 'sha256']
        );
        $this->assertNotFalse($leafCsr);

        $leafCert = openssl_csr_sign(
            $leafCsr,
            $rootCert,
            $rootKey,
            365,
            ['config' => $config, 'x509_extensions' => 'usr_cert', 'digest_alg' => 'sha256'],
            0x2002
        );
        $this->assertNotFalse($leafCert);

        openssl_x509_export($rootCert, $rootPem);
        openssl_x509_export($leafCert, $leafPem);

        return [
            'rootPem' => $rootPem,
            'rootKey' => $rootKey,
            'leafPem' => $leafPem,
            'leafSerialHex' => (new X509CertificateInfoExtractor())->extract($leafPem, $rootPem)['serialNumberHex'],
        ];
    }

    /**
     * @param array{rootPem:string,rootKey:\OpenSSLAsymmetricKey,leafPem:string,leafSerialHex:string} $material
     */
    private function ocspResponseDer(array $material, ?string $nonce = null, string $status = 'good'): string
    {
        $issuerNameDer = (new X509NameDerExtractor())->extractSubjectNameDer($material['rootPem']);
        $issuerKey = (new X509PublicKeyDerExtractor())->extractSubjectPublicKeyBitStringValue($material['rootPem']);
        $certId = Der::sequence(
            Der::algorithmIdentifier('2b0e03021a', withNull: true)
            . Der::octetString(sha1($issuerNameDer, true))
            . Der::octetString(sha1($issuerKey, true))
            . Der::integerFromHex($material['leafSerialHex'])
        );
        $single = Der::sequence(
            $certId
            . $this->ocspStatus($status)
            . Der::generalizedTime('20260524110000Z')
            . Der::contextSpecificConstructed(0, Der::generalizedTime('20260525110000Z'))
        );
        $extensions = $nonce === null ? '' : Der::contextSpecificConstructed(
            1,
            Der::sequence(Der::sequence(
                Der::oid('2b0601050507300102')
                . Der::octetString($nonce)
            ))
        );
        $tbs = Der::sequence(
            Der::contextSpecificConstructed(1, $issuerNameDer)
            . Der::generalizedTime('20260524110500Z')
            . Der::sequence($single)
            . $extensions
        );

        openssl_sign($tbs, $signature, $material['rootKey'], OPENSSL_ALGO_SHA256);

        $basic = Der::sequence(
            $tbs
            . Der::algorithmIdentifier('2a864886f70d01010b', withNull: true)
            . $this->bitString($signature)
            . Der::contextSpecificConstructed(0, Der::sequence(
                (new CertificateFormatNormalizer())->toDer($material['rootPem'])
            ))
        );
        $responseBytes = Der::sequence(
            Der::oid('2b0601050507300101')
            . Der::octetString($basic)
        );

        return Der::sequence($this->enumerated(0) . Der::contextSpecificConstructed(0, $responseBytes));
    }

    /**
     * @param array{rootPem:string,rootKey:\OpenSSLAsymmetricKey,leafPem:string,leafSerialHex:string} $material
     * @param list<string> $revokedSerials
     */
    private function crlDer(array $material, array $revokedSerials = []): string
    {
        $issuerNameDer = (new X509NameDerExtractor())->extractSubjectNameDer($material['rootPem']);
        $revoked = '';

        foreach ($revokedSerials as $serial) {
            $revoked .= Der::sequence(
                Der::integerFromHex($serial)
                . Der::generalizedTime('20260524100000Z')
            );
        }

        $tbs = Der::sequence(
            Der::integer(1)
            . Der::algorithmIdentifier('2a864886f70d01010b', withNull: true)
            . $issuerNameDer
            . Der::generalizedTime('20260524100000Z')
            . Der::generalizedTime('20260525100000Z')
            . ($revoked === '' ? '' : Der::sequence($revoked))
        );

        openssl_sign($tbs, $signature, $material['rootKey'], OPENSSL_ALGO_SHA256);

        return Der::sequence(
            $tbs
            . Der::algorithmIdentifier('2a864886f70d01010b', withNull: true)
            . $this->bitString($signature)
        );
    }

    private function ocspStatus(string $status): string
    {
        return match ($status) {
            'revoked' => Der::contextSpecificConstructed(
                1,
                Der::generalizedTime('20260524103000Z')
            ),
            'unknown' => Der::contextSpecificImplicit(2, ''),
            default => Der::contextSpecificImplicit(0, ''),
        };
    }

    private function bitString(string $value): string
    {
        return "\x03" . Der::length(strlen($value) + 1) . "\x00" . $value;
    }

    private function enumerated(int $value): string
    {
        return "\x0A\x01" . chr($value);
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
            '[ usr_cert ]',
            'basicConstraints = critical, CA:false',
            'keyUsage = critical, digitalSignature, nonRepudiation',
            'extendedKeyUsage = emailProtection',
            'subjectKeyIdentifier = hash',
            'authorityKeyIdentifier = keyid,issuer',
        ]));

        return $path;
    }
}
