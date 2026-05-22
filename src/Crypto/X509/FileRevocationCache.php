<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class FileRevocationCache implements RevocationCacheInterface
{
    public function __construct(
        private string $cacheDirectory
    ) {}

    public function get(string $type, string $key): ?string
    {
        $path = $this->path($type, $key);

        if (! is_file($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return $content === false
            ? null
            : $content;
    }

    public function put(string $type, string $key, string $der): void
    {
        if (! is_dir($this->cacheDirectory)) {
            mkdir($this->cacheDirectory, recursive: true);
        }

        file_put_contents($this->path($type, $key), $der);
    }

    private function path(string $type, string $key): string
    {
        $extension = match ($type) {
            'ocsp' => 'ocsp',
            'crl' => 'crl',
            default => 'rev',
        };

        return rtrim($this->cacheDirectory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . sha1($type . ':' . $key)
            . '.'
            . $extension;
    }
}
