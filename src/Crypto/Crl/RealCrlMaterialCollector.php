<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Crl;

use NihilLabs\Pades\Crypto\X509\RealCertificateChainCollector;
use RuntimeException;

final readonly class RealCrlMaterialCollector
{
    public function __construct(
        private ?RealCertificateChainCollector $chainCollector = null,
        private ?CrlUrlResolver $urlResolver = null,
        private ?CrlDownloader $downloader = null
    ) {}

    /**
     * @param array<string> $candidateCertificatesPem
     */
    public function collect(
        string $signerCertificatePem,
        array $candidateCertificatesPem = []
    ): RealCrlMaterial {
        $chainData = ($this->chainCollector ?? new RealCertificateChainCollector())
            ->collect(
                signerCertificatePem: $signerCertificatePem,
                candidateCertificatesPem: $candidateCertificatesPem
            );

        $certificates = [
            $chainData['signer'],
            ...$chainData['chain'],
            ...$candidateCertificatesPem,
        ];

        $urls = $this->crlUrlsFromCertificates($certificates);

        if ($urls === []) {
            throw new RuntimeException('URL CRL nao encontrada na cadeia de certificados.');
        }

        $crlsDer = [];

        foreach ($urls as $url) {
            $crl = ($this->downloader ?? new CrlDownloader())
                ->download($url);

            if ($crl !== null) {
                $crlsDer[] = $crl;
            }
        }

        $crlsDer = $this->uniqueDerObjects($crlsDer);

        if ($crlsDer === []) {
            throw new RuntimeException('CRL real nao recebida.');
        }

        return new RealCrlMaterial(
            crlsDer: $crlsDer,
            crlUrls: $urls,
            certificateChainPem: $chainData['chain']
        );
    }

    /**
     * @param array<string> $certificatesPem
     * @return array<string>
     */
    private function crlUrlsFromCertificates(array $certificatesPem): array
    {
        $urls = [];
        $seenCertificates = [];

        foreach ($certificatesPem as $certificatePem) {
            $fingerprint = hash('sha256', $certificatePem);

            if (isset($seenCertificates[$fingerprint])) {
                continue;
            }

            $seenCertificates[$fingerprint] = true;

            foreach (($this->urlResolver ?? new CrlUrlResolver())->resolve($certificatePem) as $url) {
                $urls[] = $url;
            }
        }

        return array_values(array_unique($urls));
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
