<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Credentials;

use NihilLabs\Pades\Signing\PemSignatureCredential;

final readonly class PemCredential
{
    /**
     * @param array<string> $certificateChainPaths
     */
    public static function fromFiles(
        string $certificatePath,
        string $privateKeyPath,
        ?string $privateKeyPassword = null,
        array $certificateChainPaths = []
    ): PemSignatureCredential {
        return PemSignatureCredential::fromFiles(
            certificatePath: $certificatePath,
            privateKeyPath: $privateKeyPath,
            privateKeyPassword: $privateKeyPassword,
            certificateChainPaths: $certificateChainPaths
        );
    }
}
