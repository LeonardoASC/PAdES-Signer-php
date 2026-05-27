<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Credentials;

use NihilLabs\Pades\Signing\PfxSignatureCredential;

final readonly class PfxCredential
{
    public static function fromFile(string $path, string $password): PfxSignatureCredential
    {
        return new PfxSignatureCredential(
            pathOrCertificate: $path,
            password: $password
        );
    }
}
