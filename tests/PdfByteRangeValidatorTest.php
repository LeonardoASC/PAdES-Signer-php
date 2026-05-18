<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfByteRangeValidator;
use PHPUnit\Framework\TestCase;

final class PdfByteRangeValidatorTest extends TestCase
{
    public function test_it_validates_real_signed_pdf_byte_range(): void
    {
        $pdf = file_get_contents(
            __DIR__ . '/Output/debug-signed.pdf'
        );

        $this->assertNotFalse($pdf);

        $isValid = (new PdfByteRangeValidator())
            ->validate($pdf);

        $this->assertTrue($isValid);
    }

    public function test_it_fails_when_byte_range_is_invalid(): void
    {
        $pdf = <<<PDF
%PDF-1.7
/ByteRange [0 10 20 5]
PDF;

        $isValid = (new PdfByteRangeValidator())
            ->validate($pdf);

        $this->assertFalse($isValid);
    }
}