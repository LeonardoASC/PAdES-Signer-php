<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Internal\Crypto\PadesCmsSigner;
use NihilLabs\Pades\Signing\SignerProviderInterface;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Validation\CmsCadesValidator;
use PHPUnit\Framework\TestCase;

final class CmsCadesValidatorTest extends TestCase
{
    public function test_it_validates_cms_cades_internally_without_openssl_cli(): void
    {
        $data = 'hello world';
        $cms = $this->cms($data);

        $result = (new CmsCadesValidator())->validate($cms, $data);

        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertSame(SignatureAlgorithmPolicy::HASH_SHA256, $result->digestAlgorithm);
        $this->assertSame(SignatureAlgorithmPolicy::SIGNATURE_RSA, $result->signatureAlgorithm);
        $this->assertTrue($result->checks['content_type_attribute']);
        $this->assertTrue($result->checks['message_digest_matches_content']);
        $this->assertTrue($result->checks['signing_certificate_v2_attribute']);
        $this->assertTrue($result->checks['ess_cert_id_v2_matches_signer_certificate']);
        $this->assertTrue($result->checks['issuer_serial_matches_signer_certificate']);
        $this->assertTrue($result->checks['signer_info_matches_certificate']);
    }

    public function test_it_rejects_message_digest_that_does_not_match_content(): void
    {
        $cms = $this->cms('hello world');

        $result = (new CmsCadesValidator())->validate($cms, 'tampered');

        $this->assertFalse($result->valid);
        $this->assertFalse($result->checks['message_digest_matches_content']);
    }

    public function test_it_validates_configured_sha384_policy(): void
    {
        $data = 'hello world';
        $policy = new SignatureAlgorithmPolicy(hashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA384);
        $cms = $this->cms($data, $policy);

        $result = (new CmsCadesValidator())->validate($cms, $data, $policy);

        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertSame(SignatureAlgorithmPolicy::HASH_SHA384, $result->digestAlgorithm);
        $this->assertTrue($result->checks['digest_algorithm_policy']);
    }

    public function test_it_validates_rsa_pss_parameters(): void
    {
        $data = 'hello world';
        $policy = new SignatureAlgorithmPolicy(signatureAlgorithm: SignatureAlgorithmPolicy::SIGNATURE_RSA_PSS);
        $cms = $this->cms($data, $policy, $this->deterministicProvider());

        $result = (new CmsCadesValidator())->validate($cms, $data, $policy);

        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertSame(SignatureAlgorithmPolicy::SIGNATURE_RSA_PSS, $result->signatureAlgorithm);
        $this->assertTrue($result->checks['signature_algorithm_parameters']);
    }

    public function test_it_validates_ecdsa_absent_parameters(): void
    {
        $data = 'hello world';
        $policy = new SignatureAlgorithmPolicy(signatureAlgorithm: SignatureAlgorithmPolicy::SIGNATURE_ECDSA);
        $cms = $this->cms($data, $policy, $this->deterministicProvider());

        $result = (new CmsCadesValidator())->validate($cms, $data, $policy);

        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertSame(SignatureAlgorithmPolicy::SIGNATURE_ECDSA, $result->signatureAlgorithm);
        $this->assertTrue($result->checks['signature_algorithm_parameters']);
    }

    public function test_it_treats_signing_time_as_configurable(): void
    {
        $data = 'hello world';
        $cms = $this->cms($data, includeSigningTime: true);

        $allowed = (new CmsCadesValidator())->validate($cms, $data, allowSigningTime: true);
        $rejected = (new CmsCadesValidator())->validate($cms, $data, allowSigningTime: false);

        $this->assertTrue($allowed->valid, implode("\n", $allowed->messages));
        $this->assertFalse($rejected->valid);
        $this->assertFalse($rejected->checks['signing_time_policy']);
    }

    public function test_pyhanko_comparison_fixture_when_available(): void
    {
        $fixture = __DIR__ . '/Fixtures/pyhanko-valid.pdf';

        if (! is_file($fixture)) {
            $this->markTestSkipped('Fixture pyhanko-valid.pdf nao esta disponivel neste checkout.');
        }

        $pdf = file_get_contents($fixture);
        $this->assertNotFalse($pdf);

        $signature = (new \NihilLabs\Pades\Pdf\EmbeddedPdfSignatureExtractor())->extractFirst($pdf);
        $result = (new CmsCadesValidator())->validate($signature->contentsDerWithoutPadding, $signature->signedData);

        $this->assertTrue($result->checks['content_type_attribute']);
        $this->assertTrue($result->checks['message_digest_matches_content']);
        $this->assertTrue($result->checks['signing_certificate_v2_attribute']);
    }

    private function cms(
        string $data,
        SignatureAlgorithmPolicy $policy = new SignatureAlgorithmPolicy(),
        ?SignerProviderInterface $provider = null,
        bool $includeSigningTime = false
    ): string {
        return (new PadesCmsSigner(
            certificate: new PfxCertificate(
                path: __DIR__ . '/Fixtures/certificate.pfx',
                password: '123456'
            ),
            signerProvider: $provider,
            algorithmPolicy: $policy,
            includeSigningTime: $includeSigningTime
        ))->signPdfByteRangeData($data);
    }

    private function deterministicProvider(): SignerProviderInterface
    {
        return new class implements SignerProviderInterface {
            public function sign(
                string $data,
                SignatureCredentialInterface $credential,
                SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy()
            ): string {
                return hash($algorithmPolicy->hashAlgorithm, $data, binary: true);
            }
        };
    }
}
