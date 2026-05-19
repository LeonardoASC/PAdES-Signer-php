<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\CmsSignatureValidator;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class CmsSignatureValidatorTest extends TestCase
{
    public function test_it_validates_real_pdf_signature_cms(): void
    {
        $pdf = SignedPdfFixture::signedPdfContent(
            'cms-signature-validator'
        );

        $binary = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($pdf);

        $isValid = (new CmsSignatureValidator())
            ->validate($binary);

        $this->assertTrue($isValid);
    }

    public function test_it_fails_for_invalid_binary(): void
    {
        $isValid = (new CmsSignatureValidator())
            ->validate('invalid');

        $this->assertFalse($isValid);
    }
}
