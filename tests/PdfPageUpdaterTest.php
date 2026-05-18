<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfPageUpdater;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfPageUpdaterTest extends TestCase
{
    public function test_it_adds_annotation_to_page(): void
    {
        $page = "<< /Type /Page /Parent 1 0 R >>";

        $updated = (new PdfPageUpdater())
            ->addAnnotation(
                pageBody: $page,
                widgetObjectNumber: 11
            );

        $this->assertStringContainsString(
            '/Annots [11 0 R]',
            $updated
        );

        $this->assertStringContainsString(
            '/Type /Page',
            $updated
        );
    }

    public function test_it_appends_annotation_when_page_already_has_annots(): void
    {
        $page = "<< /Type /Page /Annots [1 0 R] >>";

        $updated = (new PdfPageUpdater())
            ->addAnnotation(
                pageBody: $page,
                widgetObjectNumber: 11
            );

        $this->assertStringContainsString(
            '/Annots [1 0 R 11 0 R]',
            $updated
        );
    }
}
