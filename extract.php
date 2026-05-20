<?php

require 'vendor/autoload.php';

use NihilLabs\Pades\Pdf\PdfSignatureExtractor;

$pdf = file_get_contents('atestado-medico-2 (1).pdf');

$cms = (new PdfSignatureExtractor())
    ->extractBinarySignatureWithoutPadding($pdf);

file_put_contents('pyhanko-signature.der', $cms);

echo "OK\n";