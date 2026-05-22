<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use PHPUnit\Framework\TestCase;

final class ByteRangeCalculatorTest extends TestCase
{
    public function test_it_calculates_byte_range(): void
    {
        $pdf = 'AAA<000000>BBB';

        $calculator = new ByteRangeCalculator();

        $byteRange = $calculator->calculate(
            pdfContent: $pdf,
            contentsStart: 3,
            contentsEnd: 11
        );

        $this->assertSame('[0 3 11 3]', $byteRange->toPdfArray());
    }

    public function test_it_extracts_signed_data_excluding_contents(): void
    {
        $pdf = 'AAA<000000>BBB';

        $calculator = new ByteRangeCalculator();

        $byteRange = $calculator->calculate(
            pdfContent: $pdf,
            contentsStart: 3,
            contentsEnd: 11
        );

        $signedData = $calculator->extractSignedData($pdf, $byteRange);

        $this->assertSame('AAABBB', $signedData);
    }
}
