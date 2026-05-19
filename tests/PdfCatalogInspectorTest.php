<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfCatalogInspector;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfCatalogInspectorTest extends TestCase
{
    public function test_it_gets_catalog_object_number(): void
    {
        $pdf = <<<PDF
1 0 obj
<< /Type /Pages >>
endobj
7 0 obj
<< /Type /Catalog /Pages 1 0 R >>
endobj
PDF;

        $inspector = new PdfCatalogInspector();

        $this->assertSame(
            7,
            $inspector->getCatalogObjectNumber($pdf)
        );
    }

    public function test_it_gets_catalog_object_body(): void
    {
        $pdf = <<<PDF
7 0 obj
<< /Type /Catalog /Pages 1 0 R >>
endobj
PDF;

        $inspector = new PdfCatalogInspector();

        $this->assertSame(
            '<< /Type /Catalog /Pages 1 0 R >>',
            $inspector->getCatalogObjectBody($pdf)
        );
    }

    public function test_it_fails_when_catalog_is_missing(): void
    {
        $inspector = new PdfCatalogInspector();

        $this->expectException(RuntimeException::class);

        $inspector->getCatalogObjectNumber('%PDF-1.7');
    }

    public function test_it_reads_latest_incremental_catalog_revision(): void
    {
        $pdf = <<<PDF
7 0 obj
<< /Type /Catalog /Pages 1 0 R >>
endobj
7 0 obj
<< /Type /Catalog /Pages 1 0 R /AcroForm 20 0 R >>
endobj
PDF;

        $inspector = new PdfCatalogInspector();

        $this->assertSame(
            '<< /Type /Catalog /Pages 1 0 R /AcroForm 20 0 R >>',
            $inspector->getCatalogObjectBody($pdf)
        );
    }
}
