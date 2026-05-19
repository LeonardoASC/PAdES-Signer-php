<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Validation;

use NihilLabs\Pades\Crypto\Crl\RealCrlMaterial;
use NihilLabs\Pades\Crypto\Crl\RealCrlMaterialCollector;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspMaterial;
use NihilLabs\Pades\Crypto\Ocsp\RealOcspMaterialCollector;
use NihilLabs\Pades\Crypto\X509\CertificateFormatNormalizer;
use RuntimeException;

final readonly class RealLtvValidationMaterialFactory
{
    public function __construct(
        private ?RealOcspMaterialCollector $ocspCollector = null,
        private ?RealCrlMaterialCollector $crlCollector = null,
        private ?CertificateFormatNormalizer $normalizer = null
    ) {}

    /**
     * @param array<string> $candidateCertificatesPem
     */
    public function create(
        string $signerCertificatePem,
        array $candidateCertificatesPem = []
    ): LtvValidationMaterial
    {
        try {
            $ocspMaterial = ($this->ocspCollector ?? new RealOcspMaterialCollector())
                ->collect(
                    signerCertificatePem: $signerCertificatePem,
                    candidateCertificatesPem: $candidateCertificatesPem
                );

            return $this->fromOcspMaterial($ocspMaterial);
        } catch (RuntimeException $exception) {
            if (! str_contains($exception->getMessage(), 'URL OCSP nao encontrada')) {
                throw $exception;
            }
        }

        $crlMaterial = ($this->crlCollector ?? new RealCrlMaterialCollector())
            ->collect(
                signerCertificatePem: $signerCertificatePem,
                candidateCertificatesPem: $candidateCertificatesPem
            );

        return $this->fromCrlMaterial($crlMaterial);
    }

    public function fromOcspMaterial(
        RealOcspMaterial $ocspMaterial
    ): LtvValidationMaterial {
        $normalizer = $this->normalizer ?? new CertificateFormatNormalizer();
        $certificatesDer = [];

        foreach ($ocspMaterial->certificateChainPem as $certificatePem) {
            $certificatesDer[] = $normalizer->normalizeToDer($certificatePem);
        }

        foreach ($ocspMaterial->responderCertificatesDer as $certificateDer) {
            $certificatesDer[] = $normalizer->normalizeToDer($certificateDer);
        }

        return new LtvValidationMaterial(
            certificatesDer: $this->uniqueDerObjects($certificatesDer),
            ocspResponsesDer: [$ocspMaterial->responseDer],
            crlsDer: []
        );
    }

    public function fromCrlMaterial(
        RealCrlMaterial $crlMaterial
    ): LtvValidationMaterial {
        $normalizer = $this->normalizer ?? new CertificateFormatNormalizer();
        $certificatesDer = [];

        foreach ($crlMaterial->certificateChainPem as $certificatePem) {
            $certificatesDer[] = $normalizer->normalizeToDer($certificatePem);
        }

        return new LtvValidationMaterial(
            certificatesDer: $this->uniqueDerObjects($certificatesDer),
            ocspResponsesDer: [],
            crlsDer: $this->uniqueDerObjects($crlMaterial->crlsDer)
        );
    }

    /**
     * @param array<string> $objects
     * @return array<string>
     */
    private function uniqueDerObjects(array $objects): array
    {
        $unique = [];
        $seen = [];

        foreach ($objects as $object) {
            $hash = hash('sha256', $object);

            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $unique[] = $object;
        }

        return $unique;
    }
}
