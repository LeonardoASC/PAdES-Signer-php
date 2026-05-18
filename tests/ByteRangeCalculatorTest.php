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
            contentsStart: 4,
            contentsEnd: 10
        );

        $this->assertSame('[0 4 10 4]', $byteRange->toPdfArray());
    }

    public function test_it_extracts_signed_data_excluding_contents(): void
    {
        $pdf = 'AAA<000000>BBB';

        $calculator = new ByteRangeCalculator();

        $byteRange = $calculator->calculate(
            pdfContent: $pdf,
            contentsStart: 4,
            contentsEnd: 10
        );

        $signedData = $calculator->extractSignedData($pdf, $byteRange);

        $this->assertSame('AAA<>BBB', $signedData);
    }
}