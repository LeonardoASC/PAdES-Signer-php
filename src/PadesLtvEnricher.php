<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\Validation\RealLtvValidationMaterialFactory;
use NihilLabs\Pades\Crypto\X509\OpenSslCertificateChainValidator;
use NihilLabs\Pades\Exception\LtvException;
use NihilLabs\Pades\Exception\PdfReadException;
use NihilLabs\Pades\Exception\PdfWriteException;
use NihilLabs\Pades\Exception\TrustStoreException;
use NihilLabs\Pades\Pdf\PdfLtaEnricher;
use NihilLabs\Pades\Pdf\PdfLtvEnricher;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;
use RuntimeException;

final readonly class PadesLtvEnricher
{
    public function __construct(
        private PdfLtvEnricher $ltEnricher = new PdfLtvEnricher(),
        private PdfLtaEnricher $ltaEnricher = new PdfLtaEnricher(),
        private RealLtvValidationMaterialFactory $materialFactory = new RealLtvValidationMaterialFactory()
    ) {}

    public function addLt(
        string $signedPdfContent,
        LtvValidationMaterial $material
    ): string {
        try {
            return $this->ltEnricher->enrich(
                signedPdfContent: $signedPdfContent,
                material: $material
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            throw new LtvException(
                'Falha ao adicionar material LT ao PDF: ' . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    /**
     * @param array<string> $candidateCertificatesPem
     */
    public function addRealLt(
        string $signedPdfContent,
        string $signerCertificatePem,
        array $candidateCertificatesPem = [],
        ?PadesTrustStore $trustStore = null
    ): string {
        $materialCertificatesPem = $candidateCertificatesPem;

        if ($trustStore !== null) {
            $chain = (new OpenSslCertificateChainValidator())
                ->validateCertificateChain(
                    signerCertificatePem: $signerCertificatePem,
                    candidateCertificatesPem: $candidateCertificatesPem,
                    trustStore: $trustStore
                );

            if (! $chain->trusted) {
                throw new TrustStoreException(
                    $chain->messages[0] ?? 'Cadeia X.509 nao ancora na trust store configurada.'
                );
            }

            $materialCertificatesPem = [
                ...$candidateCertificatesPem,
                ...$trustStore->getTrustedCertificatesPem(),
            ];
        }

        return $this->addLt(
            signedPdfContent: $signedPdfContent,
            material: $this->materialFactory->create(
                signerCertificatePem: $signerCertificatePem,
                candidateCertificatesPem: $materialCertificatesPem
            )
        );
    }

    public function addLta(
        string $ltPdfContent,
        TimestampProviderInterface $timestampProvider,
        ?string $fieldName = null
    ): string {
        try {
            return $this->ltaEnricher->addArchiveTimestamp(
                ltPdfContent: $ltPdfContent,
                timestampProvider: $timestampProvider,
                fieldName: $fieldName
            );
        } catch (InvalidArgumentException | RuntimeException $exception) {
            throw new LtvException(
                'Falha ao adicionar DocTimeStamp LTA ao PDF: ' . $exception->getMessage(),
                previous: $exception
            );
        }
    }

    public function addLtFile(
        string $inputPdf,
        string $outputPdf,
        LtvValidationMaterial $material
    ): void {
        $this->write(
            outputPdf: $outputPdf,
            content: $this->addLt(
                signedPdfContent: $this->read($inputPdf),
                material: $material
            )
        );
    }

    /**
     * @param array<string> $candidateCertificatesPem
     */
    public function addRealLtFile(
        string $inputPdf,
        string $outputPdf,
        string $signerCertificatePem,
        array $candidateCertificatesPem = [],
        ?PadesTrustStore $trustStore = null
    ): void {
        $this->write(
            outputPdf: $outputPdf,
            content: $this->addRealLt(
                signedPdfContent: $this->read($inputPdf),
                signerCertificatePem: $signerCertificatePem,
                candidateCertificatesPem: $candidateCertificatesPem,
                trustStore: $trustStore
            )
        );
    }

    public function addLtaFile(
        string $inputPdf,
        string $outputPdf,
        TimestampProviderInterface $timestampProvider,
        ?string $fieldName = null
    ): void {
        $this->write(
            outputPdf: $outputPdf,
            content: $this->addLta(
                ltPdfContent: $this->read($inputPdf),
                timestampProvider: $timestampProvider,
                fieldName: $fieldName
            )
        );
    }

    private function read(string $inputPdf): string
    {
        if (! is_file($inputPdf) || ! is_readable($inputPdf)) {
            throw new PdfReadException("PDF nao encontrado ou ilegivel: {$inputPdf}");
        }

        $pdf = file_get_contents($inputPdf);

        if ($pdf === false) {
            throw new PdfReadException("PDF nao encontrado ou ilegivel: {$inputPdf}");
        }

        return $pdf;
    }

    private function write(string $outputPdf, string $content): void
    {
        if (file_put_contents($outputPdf, $content) === false) {
            throw new PdfWriteException("Nao foi possivel escrever PDF: {$outputPdf}");
        }
    }
}
