<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspResponseCache
{
    public function __construct(
        private string $cacheDirectory
    ) {}

    public function put(
        string $key,
        string $responseDer
    ): void {
        if (! is_dir($this->cacheDirectory)) {
            mkdir(
                $this->cacheDirectory,
                recursive: true
            );
        }

        file_put_contents(
            $this->path($key),
            $responseDer
        );
    }

    public function get(
        string $key
    ): ?string {
        $path = $this->path($key);

        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);

        return $content === false
            ? null
            : $content;
    }

    private function path(
        string $key
    ): string {
        return rtrim(
            $this->cacheDirectory,
            DIRECTORY_SEPARATOR
        )
        . DIRECTORY_SEPARATOR
        . sha1($key)
        . '.ocsp';
    }
}