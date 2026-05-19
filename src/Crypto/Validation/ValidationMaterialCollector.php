<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\CertificateChainBuilder;
use RuntimeException;

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
                fn(string $pem): string => $this->certificateDer($pem),
                $chain
            )
        );
    }

    private function certificateDer(
        string $certificate
    ): string {
        if (str_starts_with($certificate, "-----BEGIN CERTIFICATE-----")) {
            $clean = preg_replace(
                '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s/',
                '',
                $certificate
            );

            if ($clean === null || $clean === '') {
                throw new RuntimeException(
                    'PEM do certificado inválido.'
                );
            }

            $der = base64_decode(
                $clean,
                true
            );

            if ($der === false) {
                throw new RuntimeException(
                    'Não foi possível converter certificado PEM para DER.'
                );
            }

            return $der;
        }

        return $certificate;
    }
}
