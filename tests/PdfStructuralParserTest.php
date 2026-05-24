<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfLineEndingNormalizer;
use NihilLabs\Pades\Pdf\PdfDictionaryReader;
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

    public function test_it_resolves_indirect_reference_chains_safely(): void
    {
        $pdf = "%PDF-1.7\n";
        $offset1 = strlen($pdf);
        $pdf .= "1 0 obj\n2 0 R\nendobj\n";
        $offset2 = strlen($pdf);
        $pdf .= "2 0 obj\n3 0 R\nendobj\n";
        $offset3 = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Catalog >>\nendobj\n";
        $xref = strlen($pdf);
        $pdf .= "xref\n0 4\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offset1);
        $pdf .= sprintf("%010d 00000 n \n", $offset2);
        $pdf .= sprintf("%010d 00000 n \n", $offset3);
        $pdf .= "trailer\n<< /Size 4 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $structure = (new PdfStructuralParser())->parse($pdf);

        $this->assertSame(3, $structure->resolveReference('1 0 R')->number);
        $this->assertStringContainsString('/Type /Catalog', $structure->resolveObject(1)->body);
    }

    public function test_it_rejects_circular_indirect_references(): void
    {
        $pdf = "%PDF-1.7\n";
        $offset1 = strlen($pdf);
        $pdf .= "1 0 obj\n2 0 R\nendobj\n";
        $offset2 = strlen($pdf);
        $pdf .= "2 0 obj\n1 0 R\nendobj\n";
        $xref = strlen($pdf);
        $pdf .= "xref\n0 3\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offset1);
        $pdf .= sprintf("%010d 00000 n \n", $offset2);
        $pdf .= "trailer\n<< /Size 3 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Referencia indireta PDF circular.');

        (new PdfStructuralParser())->parse($pdf)->resolveObject(1);
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

    public function test_it_rejects_encrypted_pdfs_explicitly(): void
    {
        $pdf = str_replace(
            'trailer' . "\n" . '<< /Size 3 /Root 1 0 R >>',
            'trailer' . "\n" . '<< /Size 3 /Root 1 0 R /Encrypt 3 0 R >>',
            $this->buildPdf()
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('PDF criptografado nao e suportado.');

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

    public function test_it_parses_non_zero_object_generations(): void
    {
        $pdf = "%PDF-1.7\n";
        $offset = strlen($pdf);
        $pdf .= "7 2 obj\n<< /Type /Catalog >>\nendobj\n";
        $xref = strlen($pdf);
        $pdf .= "xref\n0 8\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i < 7; $i++) {
            $pdf .= "0000000000 65535 f \n";
        }

        $pdf .= sprintf("%010d 00002 n \n", $offset);
        $pdf .= "trailer\n<< /Size 8 /Root 7 2 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $structure = (new PdfStructuralParser())->parse($pdf);

        $this->assertSame(7, $structure->getObject(7)->number);
        $this->assertSame(2, $structure->getObject(7)->generation);
        $this->assertSame('7 2 R', $structure->latestTrailer()->getReference('Root'));
    }

    public function test_it_does_not_end_objects_on_endobj_inside_stream_bytes(): void
    {
        $stream = "1 0 obj\nthis is stream data\nendobj\nendstream\nstill stream data";
        $pdf = "%PDF-1.7\n";
        $offset1 = strlen($pdf);
        $pdf .= "1 0 obj\n"
            . "<< /Length " . strlen($stream) . " >>\n"
            . "stream\n"
            . $stream
            . "\nendstream\n"
            . "endobj\n";
        $offset2 = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Catalog >>\nendobj\n";
        $xref = strlen($pdf);
        $pdf .= "xref\n0 3\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offset1);
        $pdf .= sprintf("%010d 00000 n \n", $offset2);
        $pdf .= "trailer\n<< /Size 3 /Root 2 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        $structure = (new PdfStructuralParser())->parse($pdf);

        $this->assertCount(2, $structure->objects);
        $this->assertStringContainsString("still stream data\nendstream", $structure->getObject(1)->body);
        $this->assertStringContainsString('/Type /Catalog', $structure->getObject(2)->body);
    }

    public function test_dictionary_reader_ignores_names_inside_strings_and_nested_values(): void
    {
        $dictionary = "<<\n"
            . "/Info (this string mentions /Root 9 0 R)\n"
            . "/Nested << /Root 8 0 R >>\n"
            . "/Root 1 0 R\n"
            . "/W [1 4 2]\n"
            . ">>";

        $reader = new PdfDictionaryReader();

        $this->assertSame('1 0 R', $reader->getReference($dictionary, 'Root'));
        $this->assertSame([1, 4, 2], $reader->getIntegerArray($dictionary, 'W'));
        $this->assertSame('<< /Root 8 0 R >>', $reader->getValue($dictionary, 'Nested'));
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
