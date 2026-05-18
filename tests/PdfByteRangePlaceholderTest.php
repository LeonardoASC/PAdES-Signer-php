<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\ByteRange;
use NihilLabs\Pades\Pdf\PdfByteRangePlaceholder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class PdfByteRangePlaceholderTest extends TestCase
{
    public function test_it_replaces_byte_range_placeholder(): void
    {
        $pdf = 'abc /ByteRange [********** ********** ********** **********] xyz';

        $placeholder = new PdfByteRangePlaceholder();

        $result = $placeholder->replace(
            $pdf,
            new ByteRange(0, 10, 20, 30)
        );

        $this->assertStringContainsString(
            '/ByteRange [0 10 20 30]',
            $result
        );

        $this->assertSame(strlen($pdf), strlen($result));
    }

    public function test_it_fails_when_byte_range_placeholder_is_missing(): void
    {
        $placeholder = new PdfByteRangePlaceholder();

        $this->expectException(RuntimeException::class);

        $placeholder->findRange('abc');
    }
}