<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfSignatureStructureValidator;
use PHPUnit\Framework\TestCase;

final class PdfSignatureStructureValidatorTest extends TestCase
{
    public function test_it_validates_signed_pdf_structure(): void
    {
        $pdf = file_get_contents(
            __DIR__ . '/Output/debug-signed.pdf'
        );

        $this->assertNotFalse($pdf);

        $result = (new PdfSignatureStructureValidator())
            ->validate($pdf);

        $this->assertTrue($result['has_signature_object']);

        $this->assertTrue($result['has_byte_range']);

        $this->assertTrue($result['has_contents']);

        $this->assertTrue($result['has_acroform']);

        $this->assertTrue($result['has_widget']);

        $this->assertTrue($result['has_annots']);

        $this->assertTrue(
            $result['has_root_in_incremental_trailer']
        );
    }
}