<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class InMemoryTrustStore implements TrustStoreInterface
{
    /**
     * @var array<string>
     */
    private array $trustedCertificatesPem;

    /**
     * @param array<string> $trustedCertificatesPem
     */
    public function __construct(array $trustedCertificatesPem = [])
    {
        $normalizer = new CertificateFormatNormalizer();
        $certificates = [];
        $seen = [];

        foreach ($trustedCertificatesPem as $certificatePem) {
            foreach ($normalizer->allToPem($certificatePem) as $pem) {
                $fingerprint = hash('sha256', $normalizer->normalizeToDer($pem));

                if (isset($seen[$fingerprint])) {
                    continue;
                }

                $seen[$fingerprint] = true;
                $certificates[] = $pem;
            }
        }

        $this->trustedCertificatesPem = $certificates;
    }

    public function getTrustedCertificatesPem(): array
    {
        return $this->trustedCertificatesPem;
    }
}
