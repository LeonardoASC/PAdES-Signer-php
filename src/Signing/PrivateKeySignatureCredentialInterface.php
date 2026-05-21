<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

interface PrivateKeySignatureCredentialInterface extends SignatureCredentialInterface
{
    public function getPrivateKey(): \OpenSSLAsymmetricKey;
}
