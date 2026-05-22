<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

interface RevocationCacheInterface
{
    public function get(string $type, string $key): ?string;

    public function put(string $type, string $key, string $der): void;
}
