<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\IcpBrasil;

use InvalidArgumentException;

final readonly class PolicyHashCalculator
{
    public function sha256(string $policyArtifactBytes): string
    {
        if ($policyArtifactBytes === '') {
            throw new InvalidArgumentException('Policy artifact bytes must not be empty.');
        }

        return hash('sha256', $policyArtifactBytes, binary: true);
    }

    public function sha256Hex(string $policyArtifactBytes): string
    {
        return strtolower(bin2hex($this->sha256($policyArtifactBytes)));
    }
}
