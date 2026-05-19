<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspResponseStatusInspector
{
    public function inspect(string $responseDer): ?string
    {
        if (! (new OcspResponseStatusExtractor())->isSuccessful($responseDer)) {
            return null;
        }

        $basicResponse = (new BasicOcspResponseExtractor())
            ->extract($responseDer);

        if ($basicResponse === null) {
            return null;
        }

        return (new CertStatusExtractor())
            ->extract($basicResponse);
    }

    public function isGood(string $responseDer): bool
    {
        return $this->inspect($responseDer) === 'good';
    }
}
