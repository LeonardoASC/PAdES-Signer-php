<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfPageInspector;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfPageInspectorTest extends TestCase
{
    public function test_it_gets_first_page_object_number(): void
    {
        $pdf = <<<PDF
1 0 obj
<< /Type /Pages >>
endobj
4 0 obj
<< /Type /Page /Parent 1 0 R >>
endobj
PDF;

        $inspector = new PdfPageInspector();

        $this->assertSame(
            4,
            $inspector->getFirstPageObjectNumber($pdf)
        );
    }

    public function test_it_gets_first_page_object_body(): void
    {
        $pdf = <<<PDF
4 0 obj
<< /Type /Page /Parent 1 0 R >>
endobj
PDF;

        $inspector = new PdfPageInspector();

        $this->assertSame(
            '<< /Type /Page /Parent 1 0 R >>',
            $inspector->getFirstPageObjectBody($pdf)
        );
    }

    public function test_it_fails_when_page_is_missing(): void
    {
        $inspector = new PdfPageInspector();

        $this->expectException(RuntimeException::class);

        $inspector->getFirstPageObjectNumber('%PDF-1.7');
    }
}