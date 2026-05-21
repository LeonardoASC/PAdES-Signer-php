<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfCatalogUpdater;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfCatalogUpdaterTest extends TestCase
{
    public function test_it_adds_acroform_to_catalog(): void
    {
        $catalog = "<< /Type /Catalog /Pages 1 0 R >>";

        $updated = (new PdfCatalogUpdater())
            ->addAcroForm(
                catalogBody: $catalog,
                acroFormObjectNumber: 20
            );

        $this->assertStringContainsString(
            '/AcroForm 20 0 R',
            $updated
        );

        $this->assertStringContainsString(
            '/Type /Catalog',
            $updated
        );

        $this->assertStringContainsString(
            '/Extensions <<',
            $updated
        );

        $this->assertStringContainsString(
            '/ESIC <<',
            $updated
        );

        $this->assertStringContainsString(
            '/BaseVersion /1.7',
            $updated
        );
    }

    public function test_it_fails_when_catalog_already_has_acroform(): void
    {
        $catalog = "<< /Type /Catalog /AcroForm 10 0 R >>";

        $this->expectException(RuntimeException::class);

        (new PdfCatalogUpdater())->addAcroForm(
            catalogBody: $catalog,
            acroFormObjectNumber: 20
        );
    }

    public function test_it_adds_dss_to_catalog(): void
    {
        $catalog = "<< /Type /Catalog /Pages 1 0 R /AcroForm 20 0 R >>";

        $updated = (new PdfCatalogUpdater())
            ->addDss(
                catalogBody: $catalog,
                dssObjectNumber: 30
            );

        $this->assertStringContainsString(
            '/DSS 30 0 R',
            $updated
        );

        $this->assertStringContainsString(
            '/AcroForm 20 0 R',
            $updated
        );
    }

    public function test_it_merges_etsi_extension_without_removing_existing_extensions(): void
    {
        $catalog = "<< /Type /Catalog /Extensions << /ADBE << /BaseVersion /1.7 /ExtensionLevel 8 >> >> >>";

        $updated = (new PdfCatalogUpdater())->ensureEtsiExtension($catalog);

        $this->assertStringContainsString('/ADBE <<', $updated);
        $this->assertStringContainsString('/ESIC <<', $updated);
        $this->assertSame(1, preg_match_all('/\/ESIC\b/', $updated));

        $updatedAgain = (new PdfCatalogUpdater())->ensureEtsiExtension($updated);

        $this->assertSame(1, preg_match_all('/\/ESIC\b/', $updatedAgain));
    }
}
