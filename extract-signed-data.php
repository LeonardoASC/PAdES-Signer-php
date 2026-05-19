<?php

require 'vendor/autoload.php';

use NihilLabs\Pades\Pdf\ByteRange;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;

$pdf = file_get_contents('tests/Output/debug-signed.pdf');

preg_match(
    '/\/ByteRange\s*\[(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\]/',
    $pdf,
    $matches
);

$byteRange = new ByteRange(
    start1: (int) $matches[1],
    length1: (int) $matches[2],
    start2: (int) $matches[3],
    length2: (int) $matches[4]
);

$signedData = (new ByteRangeCalculator())
    ->extractSignedData($pdf, $byteRange);

file_put_contents('tests/Output/signed-data.bin', $signedData);

echo "signed-data.bin exportado\n";