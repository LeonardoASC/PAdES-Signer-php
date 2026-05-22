<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

interface TrustStoreInterface
{
    /**
     * @return array<string>
     */
    public function getTrustedCertificatesPem(): array;
}
