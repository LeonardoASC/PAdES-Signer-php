<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\IncrementalPdfWriter;
use PHPUnit\Framework\TestCase;

final class IncrementalPdfWriterTest extends TestCase
{
    public function test_it_appends_object_incrementally(): void
    {
        $pdf = "%PDF-1.7\nstartxref\n9\n%%EOF\n";

        $writer = new IncrementalPdfWriter();

        $updated = $writer->appendObject(
            pdfContent: $pdf,
            objectNumber: 10,
            objectBody: "<< /Test true >>"
        );

        $this->assertStringContainsString("10 0 obj", $updated);
        $this->assertStringContainsString("<< /Test true >>", $updated);
        $this->assertStringContainsString("xref", $updated);
        $this->assertStringContainsString("startxref", $updated);
        $this->assertStringContainsString("%%EOF", $updated);
        $this->assertStringContainsString("/Prev 9", $updated);
    }

    public function test_it_reads_last_startxref(): void
    {
        $pdf = "%PDF-1.7\nstartxref\n123\n%%EOF\nstartxref\n456\n%%EOF\n";

        $writer = new IncrementalPdfWriter();

        $this->assertSame(456, $writer->getLastStartXref($pdf));
    }

    public function test_it_appends_multiple_objects_incrementally(): void
    {
        $pdf = "%PDF-1.7\nstartxref\n9\n%%EOF\n";

        $writer = new IncrementalPdfWriter();

        $updated = $writer->appendObjects(
            pdfContent: $pdf,
            objects: [
                10 => "<< /First true >>",
                11 => "<< /Second true >>",
            ]
        );

        $this->assertStringContainsString("10 0 obj", $updated);
        $this->assertStringContainsString("11 0 obj", $updated);
        $this->assertStringContainsString("<< /First true >>", $updated);
        $this->assertStringContainsString("<< /Second true >>", $updated);
        $this->assertStringContainsString("/Prev 9", $updated);
    }
}
