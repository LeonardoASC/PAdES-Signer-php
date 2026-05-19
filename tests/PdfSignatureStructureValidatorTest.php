<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfSignatureStructureValidator;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class PdfSignatureStructureValidatorTest extends TestCase
{
    public function test_it_validates_signed_pdf_structure(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'pdf-signature-structure-validator'
        );

        $result = (new PdfSignatureStructureValidator())
            ->validate($pdf);

        $this->assertTrue($result['has_signature_object']);

        $this->assertTrue($result['has_signature_dictionary']);

        $this->assertTrue($result['has_pades_subfilter']);

        $this->assertTrue($result['has_byte_range']);

        $this->assertTrue($result['has_contents']);

        $this->assertTrue($result['has_acroform']);

        $this->assertTrue($result['has_fields']);

        $this->assertTrue($result['has_widget']);

        $this->assertTrue($result['has_signature_field']);

        $this->assertTrue($result['has_signature_value_reference']);

        $this->assertTrue($result['has_annots']);

        $this->assertTrue($result['has_widget_in_acroform_fields']);

        $this->assertTrue($result['has_widget_in_page_annots']);

        $this->assertTrue(
            $result['has_root_in_incremental_trailer']
        );

        $this->assertTrue($result['has_incremental_xref']);
    }
}
