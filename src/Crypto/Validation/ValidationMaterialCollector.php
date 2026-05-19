<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

use NihilLabs\Pades\Certificate\PfxCertificate;

final readonly class ValidationMaterialCollector
{
    public function collect(
        PfxCertificate $certificate
    ): ValidationMaterial {
        return new ValidationMaterial(
            certificatesDer: [
                $this->certificateDer(
                    $certificate->getPublicCertificate()
                ),
            ]
        );
    }

    private function certificateDer(
        string $pem
    ): string {
        $clean = preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s+/',
            '',
            $pem
        );

        return base64_decode(
            $clean,
            strict: true
        );
    }
}