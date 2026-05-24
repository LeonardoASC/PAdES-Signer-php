<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final class AiaCaIssuersDownloader
{
    /**
     * @var array<string, string|null>
     */
    private static array $cache = [];

    public function __construct(
        private int $timeoutSeconds = 20
    ) {}

    public function download(string $url): ?string
    {
        if (array_key_exists($url, self::$cache)) {
            return self::$cache[$url];
        }

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
            self::$cache[$url] = null;

            return null;
        }

        self::$cache[$url] = $content;

        return $content;
    }
}
