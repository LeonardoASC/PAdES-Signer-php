<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\CertificateChainBuilder;

final readonly class ValidationMaterialCollector
{
    public function __construct(
        private ?CertificateChainBuilder $chainBuilder = null
    ) {}

    public function collect(
        PfxCertificate $certificate
    ): ValidationMaterial {
        $chainBuilder = $this->chainBuilder
            ?? new CertificateChainBuilder();

        $chain = $chainBuilder->build(
            $certificate->getPublicCertificate()
        );

        return new ValidationMaterial(
            certificatesDer: array_map(
                fn (string $pem): string => $this->certificateDer($pem),
                $chain
            )
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