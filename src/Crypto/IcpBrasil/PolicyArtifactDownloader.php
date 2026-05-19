<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\IcpBrasil;

class PolicyArtifactDownloader extends LpaDownloader
{
    public function downloadArtifact(string $url): string
    {
        return $this->download($url);
    }
}
