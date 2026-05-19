<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\IcpBrasil;

use RuntimeException;
use ZipArchive;

class LpaDownloader
{
    public const PADES_LPA_DER_URL = 'http://politicas.icpbrasil.gov.br/LPA_PAdES.der';
    public const PADES_LPA_CMS_URL = 'http://politicas.icpbrasil.gov.br/LPA_PAdES.p7s';

    /**
     * @param array<int, string> $padesLpaUrls
     */
    public function __construct(
        private readonly array $padesLpaUrls = [self::PADES_LPA_DER_URL],
        private readonly int $timeoutSeconds = 30,
        private readonly int $retries = 2,
        private readonly string $userAgent = 'PAdES-Core/icp-policy'
    ) {}

    public function downloadPadesLpa(): string
    {
        $lastError = null;

        foreach ($this->padesLpaUrls as $url) {
            try {
                return $this->download($url);
            } catch (RuntimeException $exception) {
                $lastError = $exception;
            }
        }

        throw new RuntimeException(
            'Cannot download ICP-Brasil PAdES LPA.',
            previous: $lastError
        );
    }

    public function download(string $url): string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            throw new RuntimeException('Invalid ICP-Brasil policy URL.');
        }

        $lastError = null;

        for ($attempt = 0; $attempt <= $this->retries; $attempt++) {
            try {
                $bytes = $this->fetch($url);

                return $this->extractZipIfNeeded($bytes);
            } catch (RuntimeException $exception) {
                $lastError = $exception;
            }
        }

        throw new RuntimeException(
            "Cannot download ICP-Brasil policy artifact: {$url}",
            previous: $lastError
        );
    }

    protected function fetch(string $url): string
    {
        if (function_exists('curl_init')) {
            return $this->fetchWithCurl($url);
        }

        return $this->fetchWithStreams($url);
    }

    private function fetchWithCurl(string $url): string
    {
        $ch = curl_init($url);

        if ($ch === false) {
            throw new RuntimeException('Cannot initialize cURL.');
        }

        curl_setopt_array($ch, [
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeoutSeconds,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_HTTPHEADER => [
                'Accept: application/octet-stream, application/pkcs7-signature, application/xml, text/xml, application/zip, */*',
            ],
        ]);

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            throw new RuntimeException('ICP-Brasil policy HTTP error: ' . $error);
        }

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new RuntimeException('ICP-Brasil policy HTTP status: ' . $statusCode);
        }

        if ($response === '') {
            throw new RuntimeException('ICP-Brasil policy response is empty.');
        }

        return $response;
    }

    private function fetchWithStreams(string $url): string
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => implode("\r\n", [
                    'Accept: application/octet-stream, application/pkcs7-signature, application/xml, text/xml, application/zip, */*',
                    'User-Agent: ' . $this->userAgent,
                ]),
                'ignore_errors' => true,
                'timeout' => $this->timeoutSeconds,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);

        if ($content === false || $content === '') {
            throw new RuntimeException('ICP-Brasil policy stream response is empty.');
        }

        return $content;
    }

    private function extractZipIfNeeded(string $bytes): string
    {
        if (! str_starts_with($bytes, "PK\x03\x04")) {
            return $bytes;
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'pades-icp-zip-');

        if ($zipPath === false) {
            throw new RuntimeException('Cannot create temporary ZIP file.');
        }

        file_put_contents($zipPath, $bytes);

        $zip = new ZipArchive();

        if ($zip->open($zipPath) !== true) {
            @unlink($zipPath);

            throw new RuntimeException('Cannot open ICP-Brasil ZIP artifact.');
        }

        $preferredExtensions = ['der', 'xml', 'p7s', 'pdf', 'asn1'];

        foreach ($preferredExtensions as $extension) {
            for ($index = 0; $index < $zip->numFiles; $index++) {
                $name = $zip->getNameIndex($index);

                if (! is_string($name) || ! str_ends_with(strtolower($name), '.' . $extension)) {
                    continue;
                }

                $content = $zip->getFromIndex($index);
                $zip->close();
                @unlink($zipPath);

                if (is_string($content) && $content !== '') {
                    return $content;
                }
            }
        }

        $zip->close();
        @unlink($zipPath);

        throw new RuntimeException('ICP-Brasil ZIP artifact does not contain a supported file.');
    }
}
