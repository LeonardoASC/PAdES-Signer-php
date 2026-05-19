<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

interface TimestampClientInterface
{
    public function requestToken(string $timestampRequestDer): string;
}