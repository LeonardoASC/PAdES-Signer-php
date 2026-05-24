<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Crypto\Validation\CryptographicPreservationStrategy;
use NihilLabs\Pades\Crypto\X509\CallbackRevocationProvider;
use NihilLabs\Pades\Crypto\X509\RevocationMaterial;
use NihilLabs\Pades\Pdf\PdfAutomaticLtvEnricher;
use NihilLabs\Pades\Pdf\PdfDocTimeStampInspector;
use NihilLabs\Pades\Pdf\PdfLtaEnricher;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Validation\PadesBaselineProfile;
use NihilLabs\Pades\Validation\PadesLtaValidator;
use PHPUnit\Framework\TestCase;

final class PadesLtaValidatorTest extends TestCase
{
    public function test_it_validates_complete_pades_b_lta_pdf(): void
    {
        $ltPdf = $this->ltPdf();

        $ltaPdf = (new PdfLtaEnricher())->addArchiveTimestamp(
            ltPdfContent: $ltPdf,
            timestampProvider: $this->timestampClient(),
            fieldName: 'ArchiveTimeStamp1'
        );

        $result = (new PadesLtaValidator())->validatePdf($ltaPdf);

        $this->assertSame(PadesBaselineProfile::B_LTA, $result->profile);
        $this->assertTrue($result->valid, implode("\n", $result->messages));
        $this->assertTrue($result->checks['document_timestamp']);
        $this->assertTrue($result->checks['archival_timestamp_after_dss']);
        $this->assertTrue($result->checks['archival_timestamp_covers_latest_revision']);
        $this->assertTrue($result->checks['archival_timestamp_chain_ordered']);
        $this->assertTrue($result->checks['archival_timestamp_token_valid']);
    }

    public function test_it_rejects_lt_pdf_without_archival_timestamp(): void
    {
        $result = (new PadesLtaValidator())->validatePdf($this->ltPdf());

        $this->assertFalse($result->valid);
        $this->assertFalse($result->checks['document_timestamp']);
    }

    public function test_it_renews_evidence_before_adding_new_archive_timestamp(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );
        $ltPdf = $this->ltPdf();
        $renewed = (new PdfLtaEnricher())->renewEvidence(
            pdfContent: $ltPdf,
            certificateChainPem: $certificate->getCertificateChain(),
            revocationProvider: $this->revocationProvider(),
            timestampProvider: $this->timestampClient(),
            strategy: new CryptographicPreservationStrategy(
                renewalIntervalDays: 1,
                refreshValidationMaterialBeforeTimestamp: true
            )
        );

        $this->assertGreaterThan(
            strlen($ltPdf),
            strlen($renewed)
        );
        $this->assertGreaterThanOrEqual(
            1,
            (new PdfDocTimeStampInspector())->documentTimestampCount($renewed)
        );
        $this->assertTrue(
            (new PdfDocTimeStampInspector())->archivalTimestampChainIsOrdered($renewed)
        );
        $this->assertTrue(
            (new PadesLtaValidator())->validatePdf($renewed)->valid
        );
    }

    public function test_it_builds_a_chain_of_archive_timestamps(): void
    {
        $first = (new PdfLtaEnricher())->addArchiveTimestamp(
            ltPdfContent: $this->ltPdf(),
            timestampProvider: $this->timestampClient(),
            fieldName: 'ArchiveTimeStamp1'
        );
        $second = (new PdfLtaEnricher())->addArchiveTimestamp(
            ltPdfContent: $first,
            timestampProvider: $this->timestampClient(),
            fieldName: 'ArchiveTimeStamp2'
        );
        $inspector = new PdfDocTimeStampInspector();

        $this->assertSame(2, $inspector->documentTimestampCount($second));
        $this->assertTrue($inspector->latestTimestampCoversLatestRevision($second));
        $this->assertTrue($inspector->archivalTimestampChainIsOrdered($second));
    }

    public function test_preservation_strategy_decides_when_to_renew(): void
    {
        $strategy = new CryptographicPreservationStrategy(renewalIntervalDays: 10);

        $this->assertFalse($strategy->shouldRenew(
            new \DateTimeImmutable('2026-01-01T00:00:00Z'),
            new \DateTimeImmutable('2026-01-05T00:00:00Z')
        ));
        $this->assertTrue($strategy->shouldRenew(
            new \DateTimeImmutable('2026-01-01T00:00:00Z'),
            new \DateTimeImmutable('2026-01-12T00:00:00Z')
        ));
    }

    private function ltPdf(): string
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        return (new PdfAutomaticLtvEnricher())->enrich(
            signedPdfContent: $this->timestampedPdf(),
            certificateChainPem: $certificate->getCertificateChain(),
            revocationProvider: $this->revocationProvider()
        );
    }

    private function timestampedPdf(): string
    {
        $output = tempnam(sys_get_temp_dir(), 'pades-lta-');
        $this->assertIsString($output);

        (new RealPdfSigner())->sign(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456',
            timestampClient: $this->timestampClient()
        );

        $pdf = file_get_contents($output);
        $this->assertNotFalse($pdf);

        return $pdf;
    }

    private function timestampClient(): TimestampClientInterface
    {
        $response = file_get_contents(__DIR__ . '/Fixtures/timestamp-response.tsr');
        $this->assertNotFalse($response);

        return new class($response) implements TimestampClientInterface {
            public function __construct(private readonly string $response) {}

            public function requestToken(string $timestampRequestDer): string
            {
                return $this->response;
            }
        };
    }

    private function revocationProvider(): CallbackRevocationProvider
    {
        return new CallbackRevocationProvider(
            fn (array $chain): RevocationMaterial => new RevocationMaterial(
                ocspResponsesDer: ["\x30\x03\x0A\x01\x00"],
                crlsDer: ["\x30\x03\x0A\x01\x01"]
            )
        );
    }
}
