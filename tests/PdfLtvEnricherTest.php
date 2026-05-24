<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Internal\Crypto\PadesCmsVerifier;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Pdf\IncrementalPdfWriter;
use NihilLabs\Pades\Pdf\Dss\PdfDssInspector;
use NihilLabs\Pades\Pdf\PdfByteRangeValidator;
use NihilLabs\Pades\Pdf\PdfCatalogInspector;
use NihilLabs\Pades\Pdf\PdfLtvEnricher;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class PdfLtvEnricherTest extends TestCase
{
    public function test_it_adds_dss_vri_incrementally_without_breaking_openssl_verification(): void
    {
        $signedPdf = SignedPdfFixture::signedPdfContent(
            'pdf-ltv-enricher'
        );

        $previousStartXref = (new IncrementalPdfWriter())
            ->getLastStartXref($signedPdf);

        $enrichedPdf = (new PdfLtvEnricher())
            ->enrich(
                signedPdfContent: $signedPdf,
                material: new LtvValidationMaterial(
                    certificatesDer: ["\x30\x01\x01"],
                    ocspResponsesDer: ["\x30\x01\x02"],
                    crlsDer: ["\x30\x01\x03"]
                )
            );

        file_put_contents(
            __DIR__ . '/Output/pdf-ltv-enricher-output.pdf',
            $enrichedPdf
        );

        $this->assertStringStartsWith($signedPdf, $enrichedPdf);
        $this->assertGreaterThan(strlen($signedPdf), strlen($enrichedPdf));

        $incrementalUpdate = substr($enrichedPdf, strlen($signedPdf));

        $this->assertStringContainsString("/Prev {$previousStartXref}", $incrementalUpdate);
        $this->assertStringContainsString('/ByteRange [0 ', $enrichedPdf);
        $this->assertStringContainsString('/DSS ', $enrichedPdf);
        $this->assertStringContainsString('/Type /DSS', $enrichedPdf);
        $this->assertStringContainsString('/Certs [', $enrichedPdf);
        $this->assertStringContainsString('/OCSPs [', $enrichedPdf);
        $this->assertStringContainsString('/CRLs [', $enrichedPdf);
        $this->assertMatchesRegularExpression('/\/VRI\s*<<\s*\/[0-9A-F]{40}\s*<</s', $enrichedPdf);

        $latestCatalog = (new PdfCatalogInspector())
            ->getCatalogObjectBody($enrichedPdf);

        $this->assertStringContainsString('/AcroForm ', $latestCatalog);
        $this->assertStringContainsString('/DSS ', $latestCatalog);

        $this->assertTrue(
            (new PdfByteRangeValidator())
                ->validate($enrichedPdf)
        );

        $parts = SignedPdfFixture::detachedCmsParts($enrichedPdf);

        $this->assertTrue(
            (new PadesCmsVerifier())
                ->verifyByteRangeSignature(
                    cmsDer: $parts['cms'],
                    signedData: $parts['signedData']
                )
        );
    }

    public function test_it_does_not_write_new_revision_when_dss_material_is_unchanged(): void
    {
        $signedPdf = SignedPdfFixture::signedPdfContent(
            'pdf-ltv-enricher-idempotent'
        );
        $material = new LtvValidationMaterial(
            certificatesDer: ["\x30\x01\x01"],
            ocspResponsesDer: ["\x30\x01\x02"],
            crlsDer: ["\x30\x01\x03"]
        );
        $enricher = new PdfLtvEnricher();

        $enrichedPdf = $enricher->enrich(
            signedPdfContent: $signedPdf,
            material: $material
        );
        $again = $enricher->enrich(
            signedPdfContent: $enrichedPdf,
            material: $material
        );

        $this->assertSame($enrichedPdf, $again);

        $inspection = (new PdfDssInspector())->inspect($again);

        $this->assertTrue($inspection['has_dss_dictionary']);
        $this->assertTrue($inspection['has_vri_dictionary']);
    }
}
