<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Crl;

use NihilLabs\Pades\Crypto\X509\X509ExtensionExtractor;

final readonly class CrlUrlResolver
{
    public function __construct(
        private ?X509ExtensionExtractor $extractor = null
    ) {}

    /**
     * @return array<string>
     */
    public function resolve(
        string $certificatePem
    ): array {
        $extractor = $this->extractor
            ?? new X509ExtensionExtractor();

        $value = $extractor->crlDistributionPoints(
            $certificatePem
        );

        if ($value === null) {
            return [];
        }

        preg_match_all('/https?:\/\/[^\s,;<>"]+/i', $value, $matches);

        return array_values(array_unique($matches[0] ?? []));
    }
}
