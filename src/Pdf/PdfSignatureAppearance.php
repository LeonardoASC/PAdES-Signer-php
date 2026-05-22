<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureAppearance
{
    public function build(string $text = 'Digitally signed document'): string
    {
        $escapedText = str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text
        );

        $stream = "q\n"
            . "0 0 499 48 re S\n"
            . "BT\n"
            . "/F1 10 Tf\n"
            . "10 24 Td\n"
            . "({$escapedText}) Tj\n"
            . "ET\n"
            . "Q\n";

        return "<<\n"
            . "/Type /XObject\n"
            . "/Subtype /Form\n"
            . "/BBox [0 0 499 48]\n"
            . "/Resources <<\n"
            . "/Font <<\n"
            . "/F1 <<\n"
            . "/Type /Font\n"
            . "/Subtype /Type1\n"
            . "/BaseFont /Helvetica\n"
            . ">>\n"
            . ">>\n"
            . ">>\n"
            . "/Length " . strlen($stream) . "\n"
            . ">>\n"
            . "stream\n"
            . $stream
            . "endstream";
    }
}
