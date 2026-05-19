<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class CertificateDownloader
{
    public function download(
        string $url
    ): ?string {
        $content = @file_get_contents($url);

        if ($content === false || $content === '') {
            return null;
        }

        return $content;
    }
}