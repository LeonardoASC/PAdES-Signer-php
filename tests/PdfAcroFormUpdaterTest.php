<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfAcroFormUpdater;
use PHPUnit\Framework\TestCase;

final class PdfAcroFormUpdaterTest extends TestCase
{
    public function test_it_merges_signature_field_without_duplicating_existing_reference(): void
    {
        $acroForm = "<< /Fields [11 0 R] /SigFlags 3 >>";

        $updated = (new PdfAcroFormUpdater())
            ->addSignatureField(
                acroFormBody: $acroForm,
                widgetObjectNumber: 11
            );

        $this->assertSame(1, preg_match_all('/11\s+0\s+R/', $updated));
        $this->assertStringContainsString('/SigFlags 3', $updated);
    }
}
