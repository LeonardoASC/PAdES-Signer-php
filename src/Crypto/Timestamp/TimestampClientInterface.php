<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

use NihilLabs\Pades\Timestamp\TimestampProviderInterface;

interface TimestampClientInterface extends TimestampProviderInterface
{
    public function requestToken(string $timestampRequestDer): string;
}
