<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class AuthorityInfoAccessParser
{
    public function ocspUrl(
        string $authorityInfoAccess
    ): ?string {
        if (
            preg_match(
                '/OCSP\s*-\s*URI:([^\s]+)/i',
                $authorityInfoAccess,
                $matches
            )
        ) {
            return trim($matches[1]);
        }

        return null;
    }

    public function caIssuersUrl(
        string $authorityInfoAccess
    ): ?string {
        if (
            preg_match(
                '/CA Issuers\s*-\s*URI:([^\s]+)/i',
                $authorityInfoAccess,
                $matches
            )
        ) {
            return trim($matches[1]);
        }

        return null;
    }
}