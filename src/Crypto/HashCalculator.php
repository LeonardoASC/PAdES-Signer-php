<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

final readonly class HashCalculator
{
    public function sha256(string $data): string
    {
        return hash('sha256', $data, binary: true);
    }

    public function sha256Hex(string $data): string
    {
        return hash('sha256', $data);
    }

    public function sha256File(string $path): string
    {
        if (! file_exists($path)) {
            throw new \InvalidArgumentException("Arquivo não encontrado: {$path}");
        }

        $hash = hash_file('sha256', $path, binary: true);

        if ($hash === false) {
            throw new \RuntimeException("Não foi possível calcular o hash do arquivo.");
        }

        return $hash;
    }
}