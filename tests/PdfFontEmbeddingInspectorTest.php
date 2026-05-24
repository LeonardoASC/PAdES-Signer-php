<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfFontEmbeddingInspector;
use PHPUnit\Framework\TestCase;

final class PdfFontEmbeddingInspectorTest extends TestCase
{
    public function test_it_detects_non_embedded_fonts_in_the_original_pdf(): void
    {
        $pdf = $this->buildPdf([
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ]);

        $inspection = (new PdfFontEmbeddingInspector())->inspect($pdf);

        $this->assertTrue((new PdfFontEmbeddingInspector())->hasNonEmbeddedFonts($pdf));
        $this->assertSame(4, $inspection[0]['objectNumber']);
        $this->assertSame('Helvetica', $inspection[0]['baseFont']);
        $this->assertFalse($inspection[0]['embedded']);
    }

    public function test_it_accepts_fonts_with_embedded_programs(): void
    {
        $pdf = $this->buildPdf([
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            3 => '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> >>',
            4 => '<< /Type /Font /Subtype /TrueType /BaseFont /EmbeddedFont /FontDescriptor 5 0 R >>',
            5 => '<< /Type /FontDescriptor /FontName /EmbeddedFont /FontFile2 6 0 R >>',
            6 => '<< /Length 0 >> stream' . "\n\n" . 'endstream',
        ]);

        $inspection = (new PdfFontEmbeddingInspector())->inspect($pdf);

        $this->assertFalse((new PdfFontEmbeddingInspector())->hasNonEmbeddedFonts($pdf));
        $this->assertSame('EmbeddedFont', $inspection[0]['baseFont']);
        $this->assertTrue($inspection[0]['embedded']);
    }

    /**
     * @param array<int, string> $objects
     */
    private function buildPdf(array $objects): string
    {
        $pdf = "%PDF-1.7\n";
        $offsets = [];

        foreach ($objects as $number => $body) {
            $offsets[$number] = strlen($pdf);
            $pdf .= "{$number} 0 obj\n{$body}\nendobj\n";
        }

        $xref = strlen($pdf);
        $size = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 {$size}\n";
        $pdf .= "0000000000 65535 f \n";

        for ($number = 1; $number < $size; $number++) {
            $pdf .= isset($offsets[$number])
                ? sprintf("%010d 00000 n \n", $offsets[$number])
                : "0000000000 65535 f \n";
        }

        return $pdf . "trailer\n<< /Size {$size} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";
    }
}
