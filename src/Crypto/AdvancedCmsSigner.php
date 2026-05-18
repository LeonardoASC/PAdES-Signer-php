<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

use NihilLabs\Pades\Certificate\PfxCertificate;
use RuntimeException;

final readonly class AdvancedCmsSigner implements CmsSignerInterface
{
    public function __construct(
        private PfxCertificate $certificate
    ) {}

    public function signDetachedDer(
        string $data
    ): string {
        throw new RuntimeException(
            'Advanced CMS signer ainda não implementado.'
        );
    }

    public function getCertificate(): PfxCertificate
    {
        return $this->certificate;
    }
}