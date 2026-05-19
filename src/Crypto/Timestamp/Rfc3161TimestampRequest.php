<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Timestamp;

use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class Rfc3161TimestampRequest
{
    public function build(string $data): string
    {
        $hashedMessage = hash('sha256', $data, binary: true);

        return Der::sequence(
            Der::integer(1)
            . $this->messageImprint($hashedMessage)
            . Der::integer(random_int(1, PHP_INT_MAX))
            . "\x01\x01\xFF"
        );
    }

    private function messageImprint(string $hashedMessage): string
    {
        return Der::sequence(
            $this->hashAlgorithm()
            . Der::octetString($hashedMessage)
        );
    }

    private function hashAlgorithm(): string
    {
        return Der::sequence(
            Der::oid('608648016503040201')
            . Der::null()
        );
    }
}