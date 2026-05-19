<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspClient
{
    public function __construct(
        private int $timeoutSeconds = 30
    ) {}

    public function request(
        string $url,
        string $requestDer
    ): ?string {
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/ocsp-request',
                    'Accept: application/ocsp-response',
                ]),
                'content' => $requestDer,
                'ignore_errors' => true,
                'timeout' => $this->timeoutSeconds,
            ],
        ]);

        $response = @file_get_contents(
            $url,
            false,
            $context
        );

        if ($response === false || $response === '') {
            return null;
        }

        return $response;
    }
}
