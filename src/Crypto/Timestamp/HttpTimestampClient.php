<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

use RuntimeException;

final readonly class HttpTimestampClient implements TimestampClientInterface
{
    public function __construct(
        private string $url,
        private int $timeoutSeconds = 15
    ) {}

    public function requestToken(
        string $timestampRequestDer
    ): string {
        $ch = curl_init($this->url);

        if ($ch === false) {
            throw new RuntimeException('Não foi possível inicializar cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $timestampRequestDer,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/timestamp-query',
                'Accept: application/timestamp-reply',
            ],
        ]);

        $response = curl_exec($ch);

        $statusCode = curl_getinfo(
            $ch,
            CURLINFO_RESPONSE_CODE
        );

        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException(
                'Erro ao consultar TSA: ' . $error
            );
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException(
                'TSA retornou status HTTP inválido: ' . $statusCode
            );
        }

        return $response;
    }
}