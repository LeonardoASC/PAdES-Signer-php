<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

interface SignatureCredentialInterface
{
    public function getCertificatePem(): string;

    /**
     * @return array<string>
     */
    public function getCertificateChainPem(): array;
}
