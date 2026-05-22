<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

interface RevocationProviderInterface
{
    /**
     * @param array<string> $certificateChainPem
     */
    public function collect(array $certificateChainPem): RevocationMaterial;
}
