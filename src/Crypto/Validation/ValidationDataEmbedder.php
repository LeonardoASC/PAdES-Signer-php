<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

use NihilLabs\Pades\Crypto\Ocsp\OcspEmbeddedResponseAttribute;
use NihilLabs\Pades\Crypto\Ocsp\OcspRevocationInfoArchivalAttribute;

final readonly class ValidationDataEmbedder
{
    /**
     * @param array<string> $ocspResponsesDer
     * @return array<string>
     */
    public function buildUnsignedAttributes(
        array $ocspResponsesDer
    ): array {
        $attributes = [];

        foreach ($ocspResponsesDer as $response) {
            $attributes[] =
                (new OcspEmbeddedResponseAttribute())
                    ->build($response);
        }

        if ($ocspResponsesDer !== []) {
            $attributes[] =
                (new OcspRevocationInfoArchivalAttribute())
                    ->build($ocspResponsesDer);
        }

        return $attributes;
    }
}