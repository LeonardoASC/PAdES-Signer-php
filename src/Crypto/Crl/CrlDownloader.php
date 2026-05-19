<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Crl;

final readonly class CrlDownloader
{
    public function __construct(
        private int $timeoutSeconds = 30
    ) {}

    public function download(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", [
                    'Accept: application/pkix-crl, application/octet-stream, */*',
                    'User-Agent: PAdES-Core/real-crl',
                ]),
                'ignore_errors' => true,
                'timeout' => $this->timeoutSeconds,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false || $content === '') {
            return null;
        }

        return $content;
    }
}
