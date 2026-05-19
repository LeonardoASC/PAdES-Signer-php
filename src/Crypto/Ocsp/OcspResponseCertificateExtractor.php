<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class OcspResponseCertificateExtractor
{
    /**
     * @return array<string>
     */
    public function extract(string $responseDer): array
    {
        $basicResponse = (new BasicOcspResponseExtractor())
            ->extract($responseDer);

        if ($basicResponse === null) {
            return [];
        }

        try {
            return $this->extractFromBasicResponse($basicResponse);
        } catch (RuntimeException) {
            return [];
        }
    }

    /**
     * @return array<string>
     */
    private function extractFromBasicResponse(string $basicResponse): array
    {
        $reader = new DerReader();
        $offset = 0;
        $basic = $reader->readTlv($basicResponse, $offset);

        if ($basic['tag'] !== 0x30) {
            return [];
        }

        $children = $reader->childrenFromContent($basic['content']);

        if (count($children) < 4) {
            return [];
        }

        $certsContainer = $children[3];

        if ($certsContainer['tag'] !== 0xA0) {
            return [];
        }

        $offset = 0;
        $certificatesSequence = $reader->readTlv($certsContainer['content'], $offset);

        if ($certificatesSequence['tag'] !== 0x30) {
            return [];
        }

        $certificates = [];

        foreach ($reader->childrenFromContent($certificatesSequence['content']) as $certificate) {
            if ($certificate['tag'] === 0x30) {
                $certificates[] = $certificate['encoded'];
            }
        }

        return $certificates;
    }
}
