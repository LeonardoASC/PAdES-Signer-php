<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

interface SignerProviderInterface
{
    public function sign(
        string $data,
        SignatureCredentialInterface $credential
    ): string;
}
