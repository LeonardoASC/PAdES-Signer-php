<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\EmbeddedPdfSignatureExtractor;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class EmbeddedPdfSignatureExtractorTest extends TestCase
{
    public function test_it_extracts_signature_field_and_sig_dictionary_with_parser(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent('embedded-signature-extractor');

        $signature = (new EmbeddedPdfSignatureExtractor())->extractFirst($pdf);

        $this->assertGreaterThan(0, $signature->fieldObjectNumber);
        $this->assertGreaterThan(0, $signature->signatureObjectNumber);
        $this->assertSame('Signature1', $signature->fieldName);
        $this->assertStringContainsString('/FT /Sig', $signature->fieldDictionary);
        $this->assertStringContainsString('/Type /Sig', $signature->signatureDictionary);
        $this->assertStringStartsWith("\x30", $signature->contentsDerWithoutPadding);
    }

    public function test_it_preserves_contents_padding_and_calculates_signed_data_digest(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent('embedded-signature-padding');

        $signature = (new EmbeddedPdfSignatureExtractor())->extractFirst($pdf);

        $this->assertGreaterThan(strlen($signature->contentsDerWithoutPadding), strlen($signature->contentsDer));
        $this->assertSame(24000, strlen($signature->contentsHex));
        $this->assertSame(64, strlen($signature->digestHex()));
        $this->assertTrue($signature->coversWholeDocument);
        $this->assertSame('signature_contents', $signature->uncoveredRanges[0]['kind']);
    }

    public function test_it_detects_unsigned_tail_after_signed_revision(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent('embedded-signature-tail') . "\n% unsigned tail";

        $signature = (new EmbeddedPdfSignatureExtractor())->extractFirst($pdf);

        $this->assertFalse($signature->coversWholeDocument);
        $this->assertSame('unsigned_tail', $signature->uncoveredRanges[1]['kind']);
    }
}
