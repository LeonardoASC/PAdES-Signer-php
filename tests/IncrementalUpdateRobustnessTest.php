<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\IncrementalPdfWriter;
use NihilLabs\Pades\Pdf\PdfIncrementalUpdateValidator;
use NihilLabs\Pades\Pdf\PdfSignatureFieldInspector;
use NihilLabs\Pades\Pdf\PdfStructuralParser;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class IncrementalUpdateRobustnessTest extends TestCase
{
    public function test_incremental_writer_preserves_previous_revision_bytes(): void
    {
        $original = $this->buildPdf();

        $updated = (new IncrementalPdfWriter())->appendObject(
            pdfContent: $original,
            objectNumber: 3,
            objectBody: '<< /Added true >>'
        );

        $this->assertStringStartsWith($original, $updated);
        $this->assertStringContainsString('/Prev ' . $this->lastStartXref($original), $updated);
    }

    public function test_incremental_validator_rejects_modified_previous_revision(): void
    {
        $original = $this->buildPdf();
        $updated = (new IncrementalPdfWriter())->appendObject($original, 3, '<< /Added true >>');
        $corrupted = substr_replace($updated, 'X', 10, 1);

        $this->expectException(RuntimeException::class);

        (new PdfIncrementalUpdateValidator())->validateAppendOnly($original, $corrupted);
    }

    public function test_it_detects_existing_signatures(): void
    {
        $pdf = $this->signFixtureTwice();

        $this->assertSame(
            2,
            (new PdfSignatureFieldInspector())->countSignatureDictionaries($pdf)
        );
    }

    public function test_it_signs_already_signed_pdf_with_multiple_signature_fields(): void
    {
        $pdf = $this->signFixtureTwice();
        $structure = (new PdfStructuralParser())->parse($pdf);

        $acroFormObjects = array_filter(
            $structure->objects,
            static fn ($object): bool => str_contains($object->body, '/SigFlags')
        );
        $latestAcroForm = end($acroFormObjects);

        $this->assertNotFalse($latestAcroForm);
        $this->assertMatchesRegularExpression('/\/Fields\s*\[.*\d+\s+0\s+R.*\d+\s+0\s+R.*\]/s', $latestAcroForm->body);
        $this->assertSame(2, (new PdfSignatureFieldInspector())->countSignatureDictionaries($pdf));
    }

    public function test_it_signs_already_cryptographically_signed_pdf(): void
    {
        $first = tempnam(sys_get_temp_dir(), 'pades-first-crypto-');
        $second = tempnam(sys_get_temp_dir(), 'pades-second-crypto-');

        $this->assertIsString($first);
        $this->assertIsString($second);

        $signer = new RealPdfSigner();
        $certificatePath = __DIR__ . '/Fixtures/certificate.pfx';

        $signer->sign(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: $first,
            certificatePath: $certificatePath,
            certificatePassword: '123456'
        );

        $firstContent = file_get_contents($first);
        $this->assertNotFalse($firstContent);
        $this->assertStringNotContainsString('/ByteRange [**********', $firstContent);

        $signer->sign(
            inputPdf: $first,
            outputPdf: $second,
            certificatePath: $certificatePath,
            certificatePassword: '123456'
        );

        $secondContent = file_get_contents($second);
        $this->assertNotFalse($secondContent);
        $this->assertStringStartsWith($firstContent, $secondContent);
        $this->assertSame(2, (new PdfSignatureFieldInspector())->countSignatureDictionaries($secondContent));
        $this->assertSame(2, preg_match_all('/\/ByteRange\s*\[0\s+/s', $secondContent));
        $this->assertStringNotContainsString('/ByteRange [**********', $secondContent);
    }

    private function signFixtureTwice(): string
    {
        $first = tempnam(sys_get_temp_dir(), 'pades-first-');
        $second = tempnam(sys_get_temp_dir(), 'pades-second-');

        $this->assertIsString($first);
        $this->assertIsString($second);

        $signer = new RealPdfSigner();
        $signer->sign(__DIR__ . '/Fixtures/sample.pdf', $first);
        $firstContent = file_get_contents($first);
        $this->assertNotFalse($firstContent);

        $signer->sign($first, $second);
        $secondContent = file_get_contents($second);
        $this->assertNotFalse($secondContent);
        $this->assertStringStartsWith($firstContent, $secondContent);

        return $secondContent;
    }

    private function buildPdf(): string
    {
        $pdf = "%PDF-1.7\n";
        $offset1 = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $offset2 = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Count 0 >>\nendobj\n";
        $xref = strlen($pdf);
        $pdf .= "xref\n0 3\n";
        $pdf .= "0000000000 65535 f \n";
        $pdf .= sprintf("%010d 00000 n \n", $offset1);
        $pdf .= sprintf("%010d 00000 n \n", $offset2);
        $pdf .= "trailer\n<< /Size 3 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return $pdf;
    }

    private function lastStartXref(string $pdf): int
    {
        preg_match_all('/startxref\s+(\d+)/', $pdf, $matches);

        return (int) end($matches[1]);
    }
}
