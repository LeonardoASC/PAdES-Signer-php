<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

use RuntimeException;

final readonly class AdvancedCmsSigner implements CmsSignerInterface
{
    public function sign(
        string $data,
        string $certificatePath,
        string $certificatePassword
    ): string {
        throw new RuntimeException(
            'Advanced CMS signer ainda não implementado.'
        );
    }
}