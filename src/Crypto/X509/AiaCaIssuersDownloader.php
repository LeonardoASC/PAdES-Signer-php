<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class AiaCaIssuersDownloader
{
    public function __construct(
        private int $timeoutSeconds = 20
    ) {}

    public function download(string $url): ?string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", [
                    'Accept: application/pkix-cert, application/octet-stream, application/x-pem-file, */*',
                    'User-Agent: PAdES-Core/real-aia',
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
