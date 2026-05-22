<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class X509CertificateValidator
{
    public function validate(
        string $certificatePem,
        ?CertificateValidationContext $context = null,
        CertificateValidationPolicy $policy = new CertificateValidationPolicy()
    ): CertificateValidationResult {
        $context ??= new CertificateValidationContext();
        $certificate = @openssl_x509_read($certificatePem);

        if ($certificate === false) {
            return new CertificateValidationResult(
                valid: false,
                messages: ['Certificado do signatario nao e um X.509 valido.']
            );
        }

        $parsed = openssl_x509_parse($certificate);

        if ($parsed === false) {
            return new CertificateValidationResult(
                valid: false,
                messages: ['Nao foi possivel parsear o certificado do signatario.']
            );
        }

        $messages = [
            ...$this->validateTemporalWindows($parsed, $context),
            ...$this->validateKeyUsage($parsed, $policy),
            ...$this->validateExtendedKeyUsage($parsed, $policy),
        ];

        return new CertificateValidationResult(
            valid: $messages === [],
            messages: $messages,
            details: [
                'serialNumberHex' => strtoupper((string) ($parsed['serialNumberHex'] ?? '')),
                'subject' => $parsed['subject'] ?? [],
                'issuer' => $parsed['issuer'] ?? [],
                'validFrom' => $parsed['validFrom_time_t'] ?? null,
                'validTo' => $parsed['validTo_time_t'] ?? null,
                'keyUsage' => $parsed['extensions']['keyUsage'] ?? null,
                'extendedKeyUsage' => $parsed['extensions']['extendedKeyUsage'] ?? null,
            ]
        );
    }

    /**
     * @param array<string, mixed> $parsed
     * @return array<string>
     */
    private function validateTemporalWindows(
        array $parsed,
        CertificateValidationContext $context
    ): array {
        $messages = [];
        $validFrom = $parsed['validFrom_time_t'] ?? null;
        $validTo = $parsed['validTo_time_t'] ?? null;

        if (! is_int($validFrom) || ! is_int($validTo)) {
            return ['Certificado do signatario nao possui periodo de validade parseavel.'];
        }

        $checks = [
            'validacao' => $context->validationTime ?? new \DateTimeImmutable(),
            'signing time' => $context->signingTime,
            'timestamp' => $context->timestampTime,
        ];

        foreach ($checks as $label => $time) {
            if ($time === null) {
                continue;
            }

            $timestamp = $time->getTimestamp();

            if ($timestamp < $validFrom) {
                $messages[] = "Certificado do signatario ainda nao era valido no tempo de {$label}.";
            }

            if ($timestamp > $validTo) {
                $messages[] = "Certificado do signatario estava expirado no tempo de {$label}.";
            }
        }

        return $messages;
    }

    /**
     * @param array<string, mixed> $parsed
     * @return array<string>
     */
    private function validateKeyUsage(
        array $parsed,
        CertificateValidationPolicy $policy
    ): array {
        $keyUsage = $parsed['extensions']['keyUsage'] ?? null;

        if (! is_string($keyUsage) || trim($keyUsage) === '') {
            return $policy->requireKeyUsage
                ? ['Certificado do signatario nao possui extensao key usage.']
                : [];
        }

        $actual = $this->normalizedTokens($keyUsage);
        $allowed = $this->normalizedTokens(implode(',', $policy->allowedKeyUsages));

        foreach ($allowed as $usage) {
            if (in_array($usage, $actual, true)) {
                return [];
            }
        }

        return ['Certificado do signatario nao permite uso criptografico de assinatura digital.'];
    }

    /**
     * @param array<string, mixed> $parsed
     * @return array<string>
     */
    private function validateExtendedKeyUsage(
        array $parsed,
        CertificateValidationPolicy $policy
    ): array {
        $extendedKeyUsage = $parsed['extensions']['extendedKeyUsage'] ?? null;

        if (! is_string($extendedKeyUsage) || trim($extendedKeyUsage) === '') {
            return $policy->requireExtendedKeyUsage
                ? ['Certificado do signatario nao possui extensao extended key usage.']
                : [];
        }

        $actual = $this->normalizedTokens($extendedKeyUsage);
        $allowed = $this->normalizedTokens(implode(',', $policy->allowedExtendedKeyUsages));

        foreach ($allowed as $usage) {
            if (in_array($usage, $actual, true)) {
                return [];
            }
        }

        return ['Certificado do signatario nao possui extended key usage aceito para assinatura.'];
    }

    /**
     * @return array<string>
     */
    private function normalizedTokens(string $value): array
    {
        $aliases = [
            'digitalsignature' => 'digitalsignature',
            'nonrepudiation' => 'contentcommitment',
            'contentcommitment' => 'contentcommitment',
            'emailprotection' => 'emailprotection',
            'emailprotection' => 'emailprotection',
            'clientauth' => 'clientauth',
            'tlswebclientauthentication' => 'clientauth',
            'codesigning' => 'codesigning',
            '13551557336' => '13551557336',
        ];

        $tokens = [];

        foreach (preg_split('/[,;\r\n]+/', $value) ?: [] as $token) {
            $normalized = strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $token));

            if ($normalized === '') {
                continue;
            }

            $tokens[] = $aliases[$normalized] ?? $normalized;
        }

        return array_values(array_unique($tokens));
    }
}
