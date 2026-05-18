<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

interface CmsSignerInterface
{
    public function signDetachedDer(
        string $data
    ): string;
}