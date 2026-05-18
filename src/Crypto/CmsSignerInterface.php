<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

interface CmsSignerInterface
{
    public function sign(
        string $data,
        string $certificatePath,
        string $certificatePassword
    ): string;
}