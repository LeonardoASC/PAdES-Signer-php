<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use PHPUnit\Framework\TestCase;

final class MinimalPdfGeneratorTest extends TestCase
{
    public function test_it_generates_a_valid_pdf(): void
    {
        $output = __DIR__ . '/Output/minimal.pdf';

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0777, true);
        }

        (new MinimalPdfGenerator())
            ->generate($output);

        $this->assertFileExists($output);

        $content = file_get_contents($output);

        $this->assertStringContainsString(
            '%PDF-',
            $content
        );

        $this->assertStringContainsString(
            'xref',
            $content
        );

        $this->assertStringContainsString(
            'trailer',
            $content
        );
    }
}