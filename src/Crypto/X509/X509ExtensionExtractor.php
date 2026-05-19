<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class X509ExtensionExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(string $certificatePem): array
    {
        $parsed = openssl_x509_parse(
            $certificatePem
        );

        if ($parsed === false) {
            return [];
        }

        return $parsed['extensions'] ?? [];
    }

    public function authorityInfoAccess(
        string $certificatePem
    ): ?string {
        $extensions = $this->extract(
            $certificatePem
        );

        return $extensions['authorityInfoAccess']
            ?? null;
    }

    public function crlDistributionPoints(
        string $certificatePem
    ): ?string {
        $extensions = $this->extract(
            $certificatePem
        );

        return $extensions['crlDistributionPoints']
            ?? null;
    }
}