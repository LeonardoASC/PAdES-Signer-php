<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use InvalidArgumentException;
use NihilLabs\Pades\Pdf\PdfSignatureContents;
use PHPUnit\Framework\TestCase;

final class PdfSignatureContentsTest extends TestCase
{
    public function test_it_encodes_der_signature_as_pdf_hex(): void
    {
        $contents = new PdfSignatureContents(reservedBytes: 10);

        $encoded = $contents->encode("\x30\x82\x01");

        $this->assertSame(
            '30820100000000000000',
            $encoded
        );
    }

    public function test_it_generates_placeholder(): void
    {
        $contents = new PdfSignatureContents(reservedBytes: 4);

        $this->assertSame(
            '00000000',
            $contents->placeholder()
        );
    }

    public function test_it_fails_when_signature_is_too_large(): void
    {
        $contents = new PdfSignatureContents(reservedBytes: 2);

        $this->expectException(InvalidArgumentException::class);

        $contents->encode("\x30\x82\x01");
    }
}