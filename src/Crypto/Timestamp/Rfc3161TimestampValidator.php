<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

use NihilLabs\Pades\Crypto\X509\InMemoryTrustStore;
use NihilLabs\Pades\Crypto\X509\OpenSslCertificateChainValidator;
use NihilLabs\Pades\Signing\ExternalSignatureCredential;
use RuntimeException;

final readonly class Rfc3161TimestampValidator
{
    public function __construct(
        private Rfc3161TimestampTokenParser $parser = new Rfc3161TimestampTokenParser(),
        private OpenSslCertificateChainValidator $chainValidator = new OpenSslCertificateChainValidator()
    ) {}

    public function validateResponse(
        string $responseDer,
        Rfc3161TimestampValidationPolicy $policy = new Rfc3161TimestampValidationPolicy()
    ): Rfc3161TimestampValidationResult {
        try {
            $info = $this->parser->parseResponse($responseDer);
        } catch (RuntimeException $exception) {
            return new Rfc3161TimestampValidationResult(false, messages: [$exception->getMessage()]);
        }

        return $this->validateInfo($info, $policy);
    }

    public function validateToken(
        string $tokenDer,
        Rfc3161TimestampValidationPolicy $policy = new Rfc3161TimestampValidationPolicy()
    ): Rfc3161TimestampValidationResult {
        try {
            $info = $this->parser->parseToken($tokenDer);
        } catch (RuntimeException $exception) {
            return new Rfc3161TimestampValidationResult(false, messages: [$exception->getMessage()]);
        }

        return $this->validateInfo($info, $policy);
    }

    private function validateInfo(
        Rfc3161TimestampTokenInfo $info,
        Rfc3161TimestampValidationPolicy $policy
    ): Rfc3161TimestampValidationResult {
        $messages = [];

        if (! in_array($info->status, [0, 1], true)) {
            $messages[] = 'TimeStampResp RFC 3161 nao foi aceito pela TSA.';
        }

        if ($info->hashAlgorithmOid === '') {
            $messages[] = 'MessageImprint RFC 3161 nao informa algoritmo de hash.';
        }

        if ($info->hashedMessage === '') {
            $messages[] = 'MessageImprint RFC 3161 vazio.';
        }

        if ($policy->expectedMessageImprint !== null && ! hash_equals($policy->expectedMessageImprint, $info->hashedMessage)) {
            $messages[] = 'MessageImprint RFC 3161 nao corresponde aos dados esperados.';
        }

        if ($policy->allowedPolicyOids !== [] && ! in_array($info->policyOid, $policy->allowedPolicyOids, true)) {
            $messages[] = 'Politica TSA RFC 3161 nao permitida.';
        }

        if ($policy->requireTsaChainValidation || $policy->tsaTrustStore !== null) {
            if ($info->certificatesPem === []) {
                $messages[] = 'TimeStampToken nao contem certificados TSA para validar cadeia.';
            } else {
                $trustStore = $policy->tsaTrustStore ?? new InMemoryTrustStore([
                    $info->certificatesPem[count($info->certificatesPem) - 1],
                ]);
                $chain = $this->chainValidator->validateCredential(
                    credential: new ExternalSignatureCredential(
                        certificatePem: $info->certificatesPem[0],
                        keyReference: 'tsa',
                        storageType: 'timestamp-authority',
                        certificateChainPem: array_slice($info->certificatesPem, 1)
                    ),
                    trustStore: $trustStore
                );

                if (! $chain->trusted) {
                    array_push($messages, ...$chain->messages);
                }
            }
        }

        return new Rfc3161TimestampValidationResult(
            valid: $messages === [],
            info: $info,
            messages: $messages
        );
    }
}
