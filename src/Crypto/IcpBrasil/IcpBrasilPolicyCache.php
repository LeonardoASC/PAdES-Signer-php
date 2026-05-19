<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\IcpBrasil;

use RuntimeException;

final class IcpBrasilPolicyCache
{
    public function __construct(
        private readonly ?string $directory = null,
        private readonly int $ttlSeconds = 86400
    ) {}

    public function getBytes(
        string $key,
        ?int $ttlSeconds = null
    ): ?string {
        $path = $this->pathFor($key);

        if (! is_file($path)) {
            return null;
        }

        $ttlSeconds ??= $this->ttlSeconds;

        if ($ttlSeconds > 0 && filemtime($path) < time() - $ttlSeconds) {
            return null;
        }

        $content = file_get_contents($path);

        return is_string($content) && $content !== ''
            ? $content
            : null;
    }

    public function putBytes(string $key, string $bytes): void
    {
        if ($bytes === '') {
            throw new RuntimeException('Cannot cache empty ICP-Brasil policy data.');
        }

        $this->ensureDirectory();

        if (file_put_contents($this->pathFor($key), $bytes) === false) {
            throw new RuntimeException('Cannot write ICP-Brasil policy cache.');
        }
    }

    /**
     * @param callable(): string $producer
     */
    public function rememberBytes(
        string $key,
        callable $producer,
        ?int $ttlSeconds = null
    ): string {
        $cached = $this->getBytes($key, $ttlSeconds);

        if ($cached !== null) {
            return $cached;
        }

        $bytes = $producer();
        $this->putBytes($key, $bytes);

        return $bytes;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getJson(
        string $key,
        ?int $ttlSeconds = null
    ): ?array {
        $bytes = $this->getBytes($key, $ttlSeconds);

        if ($bytes === null) {
            return null;
        }

        $decoded = json_decode($bytes, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function putJson(string $key, array $data): void
    {
        $json = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );

        if (! is_string($json)) {
            throw new RuntimeException('Cannot encode ICP-Brasil policy cache.');
        }

        $this->putBytes($key, $json);
    }

    private function pathFor(string $key): string
    {
        return $this->directory()
            . DIRECTORY_SEPARATOR
            . hash('sha256', $key)
            . '.cache';
    }

    private function directory(): string
    {
        if ($this->directory !== null && $this->directory !== '') {
            return $this->directory;
        }

        return sys_get_temp_dir()
            . DIRECTORY_SEPARATOR
            . 'pades-core'
            . DIRECTORY_SEPARATOR
            . 'icp-brasil-policy';
    }

    private function ensureDirectory(): void
    {
        $directory = $this->directory();

        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new RuntimeException('Cannot create ICP-Brasil policy cache directory.');
        }
    }
}
