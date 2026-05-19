<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class CertStatusExtractor
{
    public function extract(
        string $basicOcspResponse
    ): ?string {
        $status = $this->extractFromSingleResponse($basicOcspResponse);

        if ($status !== null) {
            return $status;
        }

        if (str_contains($basicOcspResponse, "\xA0")) {
            return 'good';
        }

        if (str_contains($basicOcspResponse, "\xA1")) {
            return 'revoked';
        }

        if (str_contains($basicOcspResponse, "\xA2")) {
            return 'unknown';
        }

        return null;
    }

    public function isGood(
        string $basicOcspResponse
    ): bool {
        return $this->extract(
            $basicOcspResponse
        ) === 'good';
    }

    private function extractFromSingleResponse(string $basicOcspResponse): ?string
    {
        try {
            $reader = new DerReader();
            $offset = 0;
            $basic = $reader->readTlv($basicOcspResponse, $offset);

            if ($basic['tag'] !== 0x30) {
                return null;
            }

            $basicChildren = $reader->childrenFromContent($basic['content']);

            if ($basicChildren === [] || $basicChildren[0]['tag'] !== 0x30) {
                return null;
            }

            $responseDataChildren = $reader->childrenFromContent(
                $basicChildren[0]['content']
            );

            foreach ($responseDataChildren as $child) {
                if ($child['tag'] !== 0x30) {
                    continue;
                }

                $responses = $reader->childrenFromContent($child['content']);

                if ($responses === [] || $responses[0]['tag'] !== 0x30) {
                    continue;
                }

                $singleResponse = $reader->childrenFromContent(
                    $responses[0]['content']
                );

                if (count($singleResponse) < 2) {
                    continue;
                }

                return $this->statusFromTag($singleResponse[1]['tag']);
            }
        } catch (RuntimeException) {
            return null;
        }

        return null;
    }

    private function statusFromTag(int $tag): ?string
    {
        return match ($tag) {
            0x80, 0xA0 => 'good',
            0xA1 => 'revoked',
            0x82, 0xA2 => 'unknown',
            default => null,
        };
    }
}
