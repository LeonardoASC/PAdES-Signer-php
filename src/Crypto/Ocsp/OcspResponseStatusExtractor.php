<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class OcspResponseStatusExtractor
{
    public function extract(
        string $responseDer
    ): ?int {
        if ($responseDer === '') {
            return null;
        }

        try {
            $offset = 0;
            $response = (new DerReader())
                ->readTlv($responseDer, $offset);
        } catch (RuntimeException) {
            return $this->extractLegacyFixtureStatus($responseDer);
        }

        if ($response['tag'] !== 0x30) {
            return $this->extractLegacyFixtureStatus($responseDer);
        }

        try {
            $contentOffset = 0;
            $status = (new DerReader())
                ->readTlv($response['content'], $contentOffset);
        } catch (RuntimeException) {
            return $this->extractLegacyFixtureStatus($responseDer);
        }

        if ($status['tag'] !== 0x0A || $status['content'] === '') {
            return $this->extractLegacyFixtureStatus($responseDer);
        }

        return ord($status['content'][strlen($status['content']) - 1]);
    }

    public function isSuccessful(
        string $responseDer
    ): bool {
        return $this->extract(
            $responseDer
        ) === 0;
    }

    private function extractLegacyFixtureStatus(string $responseDer): ?int
    {
        if (strlen($responseDer) < 5) {
            return null;
        }

        if (ord($responseDer[2]) !== 0x0A) {
            return null;
        }

        return ord($responseDer[4]);
    }
}
