<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

use NihilLabs\Pades\Crypto\X509\CertificateValidationContext;
use NihilLabs\Pades\Crypto\X509\CertificateValidationPolicy;
use NihilLabs\Pades\Crypto\X509\InMemoryTrustStore;
use NihilLabs\Pades\Crypto\X509\OpenSslCertificateChainValidator;
use NihilLabs\Pades\Crypto\X509\X509CertificateValidator;
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

        if ($policy->expectedMessage !== null) {
            $expectedImprint = $this->hashExpectedMessage(
                data: $policy->expectedMessage,
                hashAlgorithmOid: $info->hashAlgorithmOid
            );

            if ($expectedImprint === null) {
                $messages[] = 'Algoritmo de hash do MessageImprint RFC 3161 nao suportado.';
            } elseif (! hash_equals($expectedImprint, $info->hashedMessage)) {
                $messages[] = 'MessageImprint RFC 3161 nao corresponde aos dados esperados.';
            }
        }

        if ($policy->expectedMessageImprint !== null && ! hash_equals($policy->expectedMessageImprint, $info->hashedMessage)) {
            $messages[] = 'MessageImprint RFC 3161 nao corresponde aos dados esperados.';
        }

        if ($policy->expectedNonce !== null && ! hash_equals($policy->expectedNonce, (string) $info->nonce)) {
            $messages[] = 'Nonce RFC 3161 nao corresponde ao valor esperado.';
        }

        if ($info->policyOid === '') {
            $messages[] = 'TimeStampToken nao informa policy OID da TSA.';
        }

        if ($policy->allowedPolicyOids !== [] && ! in_array($info->policyOid, $policy->allowedPolicyOids, true)) {
            $messages[] = 'Politica TSA RFC 3161 nao permitida.';
        }

        if ($policy->requireTokenSignatureValidation && ! $this->verifyTokenSignature($info->tokenDer)) {
            $messages[] = 'Assinatura criptografica do TimeStampToken RFC 3161 invalida.';
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

        if ($policy->requireTsaExtendedKeyUsage) {
            if ($info->certificatesPem === []) {
                $messages[] = 'TimeStampToken nao contem certificado TSA para validar EKU.';
            } else {
                $tsa = (new X509CertificateValidator())->validate(
                    certificatePem: $info->certificatesPem[0],
                    context: new CertificateValidationContext(timestampTime: $info->genTime),
                    policy: new CertificateValidationPolicy(
                        allowedExtendedKeyUsages: ['timeStamping', '1.3.6.1.5.5.7.3.8'],
                        requireExtendedKeyUsage: true
                    )
                );

                if (! $tsa->valid) {
                    array_push($messages, ...$tsa->messages);
                }
            }
        }

        return new Rfc3161TimestampValidationResult(
            valid: $messages === [],
            info: $info,
            messages: $messages
        );
    }

    private function hashExpectedMessage(string $data, string $hashAlgorithmOid): ?string
    {
        $algorithm = match ($hashAlgorithmOid) {
            '2.16.840.1.101.3.4.2.1' => 'sha256',
            '2.16.840.1.101.3.4.2.2' => 'sha384',
            '2.16.840.1.101.3.4.2.3' => 'sha512',
            '1.3.14.3.2.26' => 'sha1',
            default => null,
        };

        return $algorithm === null ? null : hash($algorithm, $data, true);
    }

    private function verifyTokenSignature(string $tokenDer): bool
    {
        if ($tokenDer === '') {
            return false;
        }

        $tokenFile = tempnam(sys_get_temp_dir(), 'pades-tst-');
        $outFile = tempnam(sys_get_temp_dir(), 'pades-tst-out-');

        if ($tokenFile === false || $outFile === false) {
            throw new RuntimeException('Nao foi possivel criar arquivos temporarios para validar timestamp.');
        }

        file_put_contents($tokenFile, $tokenDer);

        $command = sprintf(
            '%s cms -verify -binary -inform DER -in %s -noverify -out %s 2>&1',
            escapeshellarg($this->resolveOpenSslBinary()),
            escapeshellarg($tokenFile),
            escapeshellarg($outFile)
        );

        exec($command, output: $output, result_code: $exitCode);

        @unlink($tokenFile);
        @unlink($outFile);

        return $exitCode === 0;
    }

    private function resolveOpenSslBinary(): string
    {
        $fromEnvironment = getenv('OPENSSL_BINARY');

        if (is_string($fromEnvironment) && $fromEnvironment !== '') {
            return $fromEnvironment;
        }

        foreach ($this->candidateBinaries() as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return 'openssl';
    }

    /**
     * @return array<string>
     */
    private function candidateBinaries(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        return [
            'C:\\Program Files\\Git\\mingw64\\bin\\openssl.exe',
            'C:\\Program Files\\Git\\usr\\bin\\openssl.exe',
            'C:\\OpenSSL-Win64\\bin\\openssl.exe',
            'C:\\OpenSSL-Win32\\bin\\openssl.exe',
        ];
    }
}
