<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfCatalogInspector;
use NihilLabs\Pades\Pdf\PdfStructuralParser;
use NihilLabs\Pades\Pdf\PdfStructureValidator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use PHPUnit\Framework\TestCase;

final class PdfModernCompatibilityTest extends TestCase
{
    public function test_it_supports_xref_streams(): void
    {
        $pdf = $this->buildXrefStreamPdf();

        $structure = (new PdfStructureValidator())->validate($pdf);

        $this->assertSame('1 0 R', $structure->latestTrailer()->getReference('Root'));
        $this->assertSame(strpos($pdf, '1 0 obj'), $structure->xrefTables[0][1]['offset']);
    }

    public function test_it_supports_hybrid_xref_table_with_xref_stream(): void
    {
        $pdf = $this->buildHybridXrefPdf();

        $structure = (new PdfStructureValidator())->validate($pdf);

        $this->assertGreaterThanOrEqual(2, count($structure->xrefTables));
        $this->assertSame('1 0 R', $structure->latestTrailer()->getReference('Root'));
        $this->assertStringContainsString('/Type /XRef', $structure->getObject(3)->body);
    }

    public function test_it_supports_ascii_hex_decoded_xref_streams(): void
    {
        $pdf = $this->buildXrefStreamPdf(filter: 'ASCIIHexDecode');

        $structure = (new PdfStructureValidator())->validate($pdf);

        $this->assertSame('1 0 R', $structure->latestTrailer()->getReference('Root'));
        $this->assertSame(strpos($pdf, '1 0 obj'), $structure->xrefTables[0][1]['offset']);
    }

    public function test_it_supports_object_streams(): void
    {
        $pdf = $this->buildObjectStreamPdf();

        $structure = (new PdfStructuralParser())->parse($pdf);

        $this->assertStringContainsString('/Type /Catalog', $structure->getObject(4)->body);
        $this->assertSame(4, (new PdfCatalogInspector())->getCatalogObjectNumber($pdf));
    }

    public function test_it_accepts_linearized_pdfs(): void
    {
        $pdf = $this->buildXrefStreamPdf(linearized: true);

        $structure = (new PdfStructureValidator())->validate($pdf);

        $this->assertStringContainsString('/Linearized', $structure->getObject(1)->body);
    }

    public function test_it_signs_pdf_without_existing_acroform(): void
    {
        $input = __DIR__ . '/Fixtures/sample.pdf';
        $output = tempnam(sys_get_temp_dir(), 'pades-no-acroform-');

        $this->assertIsString($output);
        (new RealPdfSigner())->sign($input, $output);

        $signed = file_get_contents($output);

        $this->assertNotFalse($signed);
        $this->assertStringContainsString('/AcroForm ', $signed);
        $this->assertStringContainsString('/Fields [', $signed);
    }

    private function buildXrefStreamPdf(bool $linearized = false, ?string $filter = null): string
    {
        $pdf = "%PDF-1.7\n";
        $offset1 = strlen($pdf);
        $catalogDictionary = $linearized
            ? '<< /Linearized 1 /Type /Catalog /Pages 2 0 R >>'
            : '<< /Type /Catalog /Pages 2 0 R >>';
        $pdf .= "1 0 obj\n{$catalogDictionary}\nendobj\n";
        $offset2 = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\n";
        $offset3 = strlen($pdf);

        $entries = ''
            . $this->xrefEntry(0, 0, 65535)
            . $this->xrefEntry(1, $offset1, 0)
            . $this->xrefEntry(1, $offset2, 0)
            . $this->xrefEntry(1, $offset3, 0);

        $stream = $filter === 'ASCIIHexDecode'
            ? strtoupper(bin2hex($entries)) . '>'
            : $entries;
        $filterEntry = $filter !== null ? " /Filter /{$filter}" : '';

        $pdf .= "3 0 obj\n"
            . "<< /Type /XRef /Size 4 /Root 1 0 R /W [1 4 2]{$filterEntry} /Length " . strlen($stream) . " >>\n"
            . "stream\n"
            . $stream . "\n"
            . "endstream\n"
            . "endobj\n"
            . "startxref\n{$offset3}\n%%EOF\n";

        return $pdf;
    }

    private function buildHybridXrefPdf(): string
    {
        $pdf = "%PDF-1.7\n";
        $offset1 = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $offset2 = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\n";
        $offset3 = strlen($pdf);

        $entries = ''
            . $this->xrefEntry(0, 0, 65535)
            . $this->xrefEntry(1, $offset1, 0)
            . $this->xrefEntry(1, $offset2, 0)
            . $this->xrefEntry(1, $offset3, 0);

        $pdf .= "3 0 obj\n"
            . "<< /Type /XRef /Size 4 /Root 1 0 R /W [1 4 2] /Length " . strlen($entries) . " >>\n"
            . "stream\n"
            . $entries . "\n"
            . "endstream\n"
            . "endobj\n";

        $xref = strlen($pdf);
        $pdf .= "xref\n0 4\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offset1);
        $pdf .= sprintf("%010d 00000 n \n", $offset2);
        $pdf .= sprintf("%010d 00000 n \n", $offset3);
        $pdf .= "trailer\n<< /Size 4 /Root 1 0 R /XRefStm {$offset3} >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }

    private function buildObjectStreamPdf(): string
    {
        $catalog = '<< /Type /Catalog /Pages 5 0 R >>';
        $pages = '<< /Type /Pages /Count 0 >>';
        $header = '4 0 5 ' . strlen($catalog) . ' ';
        $decoded = $header . $catalog . $pages;
        $encoded = gzcompress($decoded);

        $pdf = "%PDF-1.7\n";
        $offset10 = strlen($pdf);
        $pdf .= "10 0 obj\n"
            . "<< /Type /ObjStm /N 2 /First " . strlen($header) . " /Filter /FlateDecode /Length " . strlen($encoded) . " >>\n"
            . "stream\n"
            . $encoded . "\n"
            . "endstream\n"
            . "endobj\n";
        $offset11 = strlen($pdf);

        $entries = ''
            . $this->xrefEntry(0, 0, 65535)
            . $this->xrefEntry(2, 10, 0)
            . $this->xrefEntry(2, 10, 1)
            . $this->xrefEntry(1, $offset10, 0)
            . $this->xrefEntry(1, $offset11, 0);

        $pdf .= "11 0 obj\n"
            . "<< /Type /XRef /Size 12 /Root 4 0 R /Index [0 1 4 2 10 2] /W [1 4 2] /Length " . strlen($entries) . " >>\n"
            . "stream\n"
            . $entries . "\n"
            . "endstream\n"
            . "endobj\n"
            . "startxref\n{$offset11}\n%%EOF\n";

        return $pdf;
    }

    private function xrefEntry(int $type, int $field2, int $field3): string
    {
        return chr($type)
            . pack('N', $field2)
            . pack('n', $field3);
    }
}
