<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use NihilLabs\Pades\Crypto\Validation\CryptographicPreservationStrategy;
use NihilLabs\Pades\Crypto\X509\RevocationProviderInterface;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;

final readonly class PdfLtaEnricher
{
    public function __construct(
        private PdfDocTimeStampSigner $docTimeStampSigner = new PdfDocTimeStampSigner(),
        private PdfAutomaticLtvEnricher $ltvEnricher = new PdfAutomaticLtvEnricher()
    ) {}

    public function addArchiveTimestamp(
        string $ltPdfContent,
        TimestampProviderInterface $timestampProvider,
        ?string $fieldName = null
    ): string {
        return $this->docTimeStampSigner->signContent(
            pdfContent: $ltPdfContent,
            timestampClient: $timestampProvider,
            signatureFieldName: $fieldName ?? 'ArchiveTimeStamp'
        );
    }

    /**
     * @param array<string> $certificateChainPem
     */
    public function renewEvidence(
        string $pdfContent,
        array $certificateChainPem,
        RevocationProviderInterface $revocationProvider,
        TimestampProviderInterface $timestampProvider,
        ?CryptographicPreservationStrategy $strategy = null
    ): string {
        $strategy ??= new CryptographicPreservationStrategy();
        $current = $pdfContent;

        if ($strategy->refreshValidationMaterialBeforeTimestamp) {
            $current = $this->ltvEnricher->enrich(
                signedPdfContent: $current,
                certificateChainPem: $certificateChainPem,
                revocationProvider: $revocationProvider
            );
        }

        return $this->addArchiveTimestamp(
            ltPdfContent: $current,
            timestampProvider: $timestampProvider,
            fieldName: 'ArchiveTimeStampRenewal'
        );
    }
}
