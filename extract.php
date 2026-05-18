<?php

require 'vendor/autoload.php';

use NihilLabs\Pades\Pdf\PdfSignatureExtractor;

$pdf = file_get_contents(
    'tests/Output/debug-signed.pdf'
);

if ($pdf === false) {
    exit('PDF não encontrado');
}

$cms = (new PdfSignatureExtractor())
    ->extractBinarySignatureWithoutPadding($pdf);

file_put_contents(
    'tests/Output/signature.der',
    $cms
);

echo "DER exportado\n";