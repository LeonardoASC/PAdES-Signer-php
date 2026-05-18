<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\PdfSignaturePlaceholder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfSignaturePlaceholderTest extends TestCase
{
    public function test_it_finds_contents_placeholder_range(): void
    {
        $pdf = 'abc /Contents <00000000> xyz';

        $placeholder = new PdfSignaturePlaceholder();

        $range = $placeholder->findContentsRange($pdf);

        $this->assertSame(15, $range['start']);
        $this->assertSame(23, $range['end']);
    }

    public function test_it_replaces_contents_placeholder(): void
    {
        $pdf = 'abc /Contents <00000000> xyz';

        $placeholder = new PdfSignaturePlaceholder();

        $result = $placeholder->replaceContents(
            $pdf,
            'AABBCCDD'
        );

        $this->assertSame(
            'abc /Contents <AABBCCDD> xyz',
            $result
        );
    }

    public function test_it_fails_when_signature_length_does_not_match(): void
    {
        $pdf = 'abc /Contents <00000000> xyz';

        $placeholder = new PdfSignaturePlaceholder();

        $this->expectException(RuntimeException::class);

        $placeholder->replaceContents($pdf, 'AABB');
    }
}