<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfSignatureExtractorTest extends TestCase
{
    public function test_it_extracts_hex_signature(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'pdf-signature-extractor-hex'
        );

        $hex = (new PdfSignatureExtractor())
            ->extractHexSignature($pdf);

        $this->assertNotEmpty($hex);

        $this->assertMatchesRegularExpression(
            '/^[0-9A-F]+$/',
            $hex
        );
    }

    public function test_it_extracts_binary_signature(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'pdf-signature-extractor-binary'
        );

        $binary = (new PdfSignatureExtractor())
            ->extractBinarySignature($pdf);

        $this->assertNotEmpty($binary);

        $this->assertStringStartsWith(
            "\x30",
            $binary
        );
    }

    public function test_it_fails_when_signature_is_missing(): void
    {
        $this->expectException(RuntimeException::class);

        (new PdfSignatureExtractor())
            ->extractHexSignature('%PDF-1.7');
    }

    public function test_it_extracts_binary_signature_without_padding(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'pdf-signature-extractor-without-padding'
        );

        $binary = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $this->assertNotEmpty($binary);

        $this->assertStringStartsWith(
            "\x30",
            $binary
        );

        $this->assertNotSame(
            "\x00",
            substr($binary, -1)
        );
    }
}
