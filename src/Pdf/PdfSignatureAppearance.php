<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureAppearance
{
    public function build(string $text = 'Digitally signed document', int|float $width = 499, int|float $height = 48): string
    {
        unset($text);

        $width = max(1, $width);
        $height = max(1, $height);
        $boxWidth = $this->pdfNumber($width);
        $boxHeight = $this->pdfNumber($height);
        $inset = min(18, max(6, min($width, $height) * 0.08));
        $innerWidth = max(1, $width - ($inset * 2));
        $innerHeight = max(1, $height - ($inset * 2));
        $markSize = min($innerHeight * 0.45, $innerWidth * 0.18);
        $markX = $inset + ($innerWidth * 0.08);
        $markY = $inset + ($innerHeight * 0.38);
        $lineStartX = $inset + ($innerWidth * 0.28);
        $lineEndX = $inset + ($innerWidth * 0.92);
        $line1Y = $inset + ($innerHeight * 0.62);
        $line2Y = $inset + ($innerHeight * 0.42);
        $line3Y = $inset + ($innerHeight * 0.24);

        $stream = "q\n"
            . "0.96 0.98 1 rg\n"
            . "0 0 {$boxWidth} {$boxHeight} re f\n"
            . "0.02 0.27 0.50 RG\n"
            . "1.5 w\n"
            . $this->pdfNumber($inset) . ' ' . $this->pdfNumber($inset) . ' '
            . $this->pdfNumber($innerWidth) . ' ' . $this->pdfNumber($innerHeight) . " re S\n"
            . "0.00 0.55 0.80 RG\n"
            . "3 w\n"
            . $this->pdfNumber($markX) . ' ' . $this->pdfNumber($markY) . " m\n"
            . $this->pdfNumber($markX + ($markSize * 0.35)) . ' '
            . $this->pdfNumber($markY - ($markSize * 0.35)) . " l\n"
            . $this->pdfNumber($markX + $markSize) . ' '
            . $this->pdfNumber($markY + ($markSize * 0.45)) . " l S\n"
            . "0.02 0.27 0.50 RG\n"
            . "2 w\n"
            . $this->pdfNumber($lineStartX) . ' ' . $this->pdfNumber($line1Y) . " m "
            . $this->pdfNumber($lineEndX) . ' ' . $this->pdfNumber($line1Y) . " l S\n"
            . $this->pdfNumber($lineStartX) . ' ' . $this->pdfNumber($line2Y) . " m "
            . $this->pdfNumber($lineEndX) . ' ' . $this->pdfNumber($line2Y) . " l S\n"
            . $this->pdfNumber($lineStartX) . ' ' . $this->pdfNumber($line3Y) . " m "
            . $this->pdfNumber($lineEndX * 0.82) . ' ' . $this->pdfNumber($line3Y) . " l S\n"
            . "Q\n";

        return "<<\n"
            . "/Type /XObject\n"
            . "/Subtype /Form\n"
            . "/BBox [0 0 {$boxWidth} {$boxHeight}]\n"
            . "/Resources <<\n"
            . ">>\n"
            . "/Length " . strlen($stream) . "\n"
            . ">>\n"
            . "stream\n"
            . $stream
            . "endstream";
    }

    private function pdfNumber(int|float $value): string
    {
        return is_float($value)
            ? rtrim(rtrim(sprintf('%.6F', $value), '0'), '.')
            : (string) $value;
    }
}
