<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class CertificateChainBuilder
{
    public function __construct(
        private ?X509ExtensionExtractor $extractor = null,
        private ?AuthorityInfoAccessParser $parser = null,
        private ?CertificateDownloader $downloader = null
    ) {}

    /**
     * @return array<string>
     */
    public function build(
        string $certificatePem
    ): array {
        $extractor = $this->extractor ?? new X509ExtensionExtractor();
        $parser = $this->parser ?? new AuthorityInfoAccessParser();
        $downloader = $this->downloader ?? new CertificateDownloader();
        $chain = [
            $certificatePem,
        ];

        $aia = $extractor
            ->authorityInfoAccess($certificatePem);

        if ($aia === null) {
            return $chain;
        }

        $issuerUrl = $parser
            ->caIssuersUrl($aia);

        if ($issuerUrl === null) {
            return $chain;
        }

        $issuerCertificate = $downloader
            ->download($issuerUrl);

        if ($issuerCertificate !== null) {
            $chain[] = $issuerCertificate;
        }

        return $chain;
    }
}
