<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampTokenParser;
use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampValidationPolicy;
use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampValidator;
use NihilLabs\Pades\Crypto\Timestamp\TimestampResponseParser;
use NihilLabs\Pades\Crypto\X509\InMemoryTrustStore;
use PHPUnit\Framework\TestCase;

final class Rfc3161TimestampValidatorTest extends TestCase
{
    public function test_it_validates_rfc3161_timestamp_response_structure_and_imprint(): void
    {
        $response = $this->timestampResponse();
        $info = (new Rfc3161TimestampTokenParser())->parseResponse($response);

        $result = (new Rfc3161TimestampValidator())->validateResponse(
            responseDer: $response,
            policy: new Rfc3161TimestampValidationPolicy(
                expectedMessageImprint: $info->hashedMessage
            )
        );

        $this->assertTrue($result->valid);
        $this->assertNotNull($result->info);
        $this->assertSame(0, $result->info->status);
        $this->assertSame('2.16.840.1.101.3.4.2.1', $result->info->hashAlgorithmOid);
        $this->assertNotEmpty($result->info->policyOid);
        $this->assertNotEmpty($result->info->certificatesPem);
    }

    public function test_it_rejects_unexpected_message_imprint(): void
    {
        $result = (new Rfc3161TimestampValidator())->validateResponse(
            responseDer: $this->timestampResponse(),
            policy: new Rfc3161TimestampValidationPolicy(
                expectedMessageImprint: str_repeat("\x00", 32)
            )
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'MessageImprint RFC 3161 nao corresponde aos dados esperados.',
            $result->messages
        );
    }

    public function test_it_validates_tsa_policy_oid(): void
    {
        $result = (new Rfc3161TimestampValidator())->validateResponse(
            responseDer: $this->timestampResponse(),
            policy: new Rfc3161TimestampValidationPolicy(
                allowedPolicyOids: ['1.2.3.4.5']
            )
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'Politica TSA RFC 3161 nao permitida.',
            $result->messages
        );
    }

    public function test_it_validates_tsa_certificate_chain(): void
    {
        $response = $this->timestampResponse();
        $info = (new Rfc3161TimestampTokenParser())->parseResponse($response);

        $result = (new Rfc3161TimestampValidator())->validateResponse(
            responseDer: $response,
            policy: new Rfc3161TimestampValidationPolicy(
                tsaTrustStore: new InMemoryTrustStore([
                    $info->certificatesPem[count($info->certificatesPem) - 1],
                ]),
                requireTsaChainValidation: true
            )
        );

        $this->assertTrue($result->valid, implode("\n", $result->messages));
    }

    public function test_it_rejects_unexpected_nonce(): void
    {
        $result = (new Rfc3161TimestampValidator())->validateResponse(
            responseDer: $this->timestampResponse(),
            policy: new Rfc3161TimestampValidationPolicy(
                expectedNonce: 'wrong-nonce'
            )
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'Nonce RFC 3161 nao corresponde ao valor esperado.',
            $result->messages
        );
    }

    public function test_it_rejects_unexpected_message_data(): void
    {
        $result = (new Rfc3161TimestampValidator())->validateResponse(
            responseDer: $this->timestampResponse(),
            policy: new Rfc3161TimestampValidationPolicy(
                expectedMessage: 'wrong timestamped data'
            )
        );

        $this->assertFalse($result->valid);
        $this->assertContains(
            'MessageImprint RFC 3161 nao corresponde aos dados esperados.',
            $result->messages
        );
    }

    public function test_it_rejects_tampered_timestamp_token_signature(): void
    {
        $token = (new TimestampResponseParser())->extractToken($this->timestampResponse());
        $tampered = substr_replace($token, ~$token[-1], -1);

        $result = (new Rfc3161TimestampValidator())->validateToken($tampered);

        $this->assertFalse($result->valid);
        $this->assertContains(
            'Assinatura criptografica do TimeStampToken RFC 3161 invalida.',
            $result->messages
        );
    }

    private function timestampResponse(): string
    {
        $response = file_get_contents(__DIR__ . '/Fixtures/timestamp-response.tsr');

        $this->assertNotFalse($response);

        return $response;
    }

}
