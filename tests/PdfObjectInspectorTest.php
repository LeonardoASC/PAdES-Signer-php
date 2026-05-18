<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfObjectInspector;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfObjectInspectorTest extends TestCase
{
    public function test_it_gets_highest_object_number(): void
    {
        $pdf = <<<PDF
1 0 obj
<<>>
endobj
5 0 obj
<<>>
endobj
3 0 obj
<<>>
endobj
PDF;

        $inspector = new PdfObjectInspector();

        $this->assertSame(
            5,
            $inspector->getHighestObjectNumber($pdf)
        );
    }

    public function test_it_gets_next_object_number(): void
    {
        $pdf = "1 0 obj\n<<>>\nendobj\n";

        $inspector = new PdfObjectInspector();

        $this->assertSame(
            2,
            $inspector->getNextObjectNumber($pdf)
        );
    }

    public function test_it_fails_when_no_objects_are_found(): void
    {
        $inspector = new PdfObjectInspector();

        $this->expectException(RuntimeException::class);

        $inspector->getHighestObjectNumber('%PDF-1.7');
    }
}