<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfLineEndingNormalizer;
use NihilLabs\Pades\Pdf\PdfStructuralParser;
use NihilLabs\Pades\Pdf\PdfStructureValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfStructuralParserTest extends TestCase
{
    public function test_it_parses_objects_xref_table_and_trailer(): void
    {
        $pdf = $this->buildPdf();

        $structure = (new PdfStructuralParser())->parse($pdf);

        $this->assertSame(2, $structure->highestObjectNumber());
        $this->assertSame('1 0 R', $structure->latestTrailer()->getReference('Root'));
        $this->assertSame(1, count($structure->xrefTables));
        $this->assertSame(strpos($pdf, '1 0 obj'), $structure->xrefTables[0][1]['offset']);
    }

    public function test_it_resolves_indirect_objects(): void
    {
        $structure = (new PdfStructuralParser())->parse($this->buildPdf());

        $object = $structure->getObject(2);

        $this->assertSame(2, $object->number);
        $this->assertStringContainsString('/Type /Pages', $object->body);
    }

    public function test_it_validates_xref_and_trailer(): void
    {
        $structure = (new PdfStructureValidator())->validate($this->buildPdf());

        $this->assertSame(2, $structure->highestObjectNumber());
    }

    public function test_it_rejects_corrupted_xref_offsets(): void
    {
        $pdf = preg_replace('/0000000009 00000 n/', '0000009999 00000 n', $this->buildPdf(), 1);

        $this->assertIsString($pdf);
        $this->expectException(RuntimeException::class);

        (new PdfStructureValidator())->validate($pdf);
    }

    public function test_structural_line_ending_normalization_does_not_touch_pdf_content(): void
    {
        $binary = "stream\r\nA\rB\nendstream";
        $normalizer = new PdfLineEndingNormalizer();

        $this->assertSame("xref\n0 1\n", $normalizer->normalizeStructuralSegment("xref\r\n0 1\r"));
        $this->assertSame($binary, $binary);
    }

    public function test_incremental_parse_preserves_original_prefix_bytes(): void
    {
        $pdf = $this->buildPdf();
        $structure = (new PdfStructuralParser())->parse($pdf);

        $this->assertSame($pdf, $structure->content);
    }

    private function buildPdf(): string
    {
        $pdf = "%PDF-1.7\n";
        $offset1 = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $offset2 = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\n";
        $xref = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 3\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offset1);
        $pdf .= sprintf("%010d 00000 n \n", $offset2);
        $pdf .= "trailer\n<< /Size 3 /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }
}
