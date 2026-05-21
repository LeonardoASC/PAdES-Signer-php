<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Timestamp;

interface TimestampProviderInterface
{
    public function requestToken(string $request): string;
}
