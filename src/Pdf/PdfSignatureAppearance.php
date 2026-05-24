<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureAppearance
{
    public function build(string $text = 'Digitally signed document', int|float $width = 499, int|float $height = 48): string
    {
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
        $textMaxWidth = max(1, ($inset + ($innerWidth * 0.92)) - $lineStartX);
        $textMaxHeight = max(1, $innerHeight * 0.78);
        $textStartY = $inset + ($innerHeight * 0.78);

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
            . "0.02 0.27 0.50 rg\n"
            . $this->renderBitmapText(
                text: $text,
                x: $lineStartX,
                topY: $textStartY,
                maxWidth: $textMaxWidth,
                maxHeight: $textMaxHeight
            )
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

    private function renderBitmapText(
        string $text,
        float $x,
        float $topY,
        float $maxWidth,
        float $maxHeight
    ): string {
        $normalized = $this->normalizeText($text);
        $scale = max(0.8, min(2.8, $maxHeight / 38));
        $maxChars = max(8, (int) floor($maxWidth / ($scale * 6)));
        $lineHeight = $scale * 9;
        $maxLines = max(1, (int) floor($maxHeight / $lineHeight));
        $lines = array_slice($this->wrapLines($normalized, $maxChars), 0, $maxLines);
        $glyphs = $this->glyphs();
        $operations = '';

        foreach ($lines as $lineIndex => $line) {
            $baselineY = $topY - (($lineIndex + 1) * $lineHeight);

            foreach (str_split($line) as $charIndex => $char) {
                $glyph = $glyphs[$char] ?? $glyphs[' '];
                $glyphX = $x + ($charIndex * $scale * 6);

                foreach ($glyph as $rowIndex => $row) {
                    foreach (str_split($row) as $columnIndex => $pixel) {
                        if ($pixel !== '1') {
                            continue;
                        }

                        $pixelX = $glyphX + ($columnIndex * $scale);
                        $pixelY = $baselineY + ((6 - $rowIndex) * $scale);
                        $operations .= $this->pdfNumber($pixelX) . ' '
                            . $this->pdfNumber($pixelY) . ' '
                            . $this->pdfNumber($scale) . ' '
                            . $this->pdfNumber($scale) . " re f\n";
                    }
                }
            }
        }

        return $operations;
    }

    /**
     * @return list<string>
     */
    private function wrapLines(string $text, int $maxChars): array
    {
        $lines = [];

        foreach (preg_split('/\R/', $text) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);

            if ($paragraph === '') {
                continue;
            }

            while (strlen($paragraph) > $maxChars) {
                $breakAt = strrpos(substr($paragraph, 0, $maxChars + 1), ' ');

                if ($breakAt === false || $breakAt < 8) {
                    $breakAt = $maxChars;
                }

                $lines[] = trim(substr($paragraph, 0, $breakAt));
                $paragraph = trim(substr($paragraph, $breakAt));
            }

            if ($paragraph !== '') {
                $lines[] = $paragraph;
            }
        }

        return $lines !== [] ? $lines : ['SIGNED DOCUMENT'];
    }

    private function normalizeText(string $text): string
    {
        $ascii = function_exists('iconv')
            ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text)
            : false;

        if ($ascii === false) {
            $ascii = $text;
        }

        $ascii = strtoupper($ascii);
        $ascii = preg_replace('/[^A-Z0-9 .:\/@()_\-]+/', ' ', $ascii) ?? '';

        return trim(preg_replace('/[ \t]+/', ' ', $ascii) ?? '');
    }

    /**
     * @return array<string, list<string>>
     */
    private function glyphs(): array
    {
        return [
            ' ' => ['00000', '00000', '00000', '00000', '00000', '00000', '00000'],
            'A' => ['01110', '10001', '10001', '11111', '10001', '10001', '10001'],
            'B' => ['11110', '10001', '10001', '11110', '10001', '10001', '11110'],
            'C' => ['01111', '10000', '10000', '10000', '10000', '10000', '01111'],
            'D' => ['11110', '10001', '10001', '10001', '10001', '10001', '11110'],
            'E' => ['11111', '10000', '10000', '11110', '10000', '10000', '11111'],
            'F' => ['11111', '10000', '10000', '11110', '10000', '10000', '10000'],
            'G' => ['01111', '10000', '10000', '10111', '10001', '10001', '01111'],
            'H' => ['10001', '10001', '10001', '11111', '10001', '10001', '10001'],
            'I' => ['11111', '00100', '00100', '00100', '00100', '00100', '11111'],
            'J' => ['00111', '00010', '00010', '00010', '00010', '10010', '01100'],
            'K' => ['10001', '10010', '10100', '11000', '10100', '10010', '10001'],
            'L' => ['10000', '10000', '10000', '10000', '10000', '10000', '11111'],
            'M' => ['10001', '11011', '10101', '10101', '10001', '10001', '10001'],
            'N' => ['10001', '11001', '10101', '10011', '10001', '10001', '10001'],
            'O' => ['01110', '10001', '10001', '10001', '10001', '10001', '01110'],
            'P' => ['11110', '10001', '10001', '11110', '10000', '10000', '10000'],
            'Q' => ['01110', '10001', '10001', '10001', '10101', '10010', '01101'],
            'R' => ['11110', '10001', '10001', '11110', '10100', '10010', '10001'],
            'S' => ['01111', '10000', '10000', '01110', '00001', '00001', '11110'],
            'T' => ['11111', '00100', '00100', '00100', '00100', '00100', '00100'],
            'U' => ['10001', '10001', '10001', '10001', '10001', '10001', '01110'],
            'V' => ['10001', '10001', '10001', '10001', '10001', '01010', '00100'],
            'W' => ['10001', '10001', '10001', '10101', '10101', '10101', '01010'],
            'X' => ['10001', '10001', '01010', '00100', '01010', '10001', '10001'],
            'Y' => ['10001', '10001', '01010', '00100', '00100', '00100', '00100'],
            'Z' => ['11111', '00001', '00010', '00100', '01000', '10000', '11111'],
            '0' => ['01110', '10001', '10011', '10101', '11001', '10001', '01110'],
            '1' => ['00100', '01100', '00100', '00100', '00100', '00100', '01110'],
            '2' => ['01110', '10001', '00001', '00010', '00100', '01000', '11111'],
            '3' => ['11110', '00001', '00001', '01110', '00001', '00001', '11110'],
            '4' => ['00010', '00110', '01010', '10010', '11111', '00010', '00010'],
            '5' => ['11111', '10000', '10000', '11110', '00001', '00001', '11110'],
            '6' => ['01110', '10000', '10000', '11110', '10001', '10001', '01110'],
            '7' => ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
            '8' => ['01110', '10001', '10001', '01110', '10001', '10001', '01110'],
            '9' => ['01110', '10001', '10001', '01111', '00001', '00001', '01110'],
            '.' => ['00000', '00000', '00000', '00000', '00000', '01100', '01100'],
            ':' => ['00000', '01100', '01100', '00000', '01100', '01100', '00000'],
            '/' => ['00001', '00010', '00010', '00100', '01000', '01000', '10000'],
            '@' => ['01110', '10001', '10111', '10101', '10111', '10000', '01110'],
            '(' => ['00010', '00100', '01000', '01000', '01000', '00100', '00010'],
            ')' => ['01000', '00100', '00010', '00010', '00010', '00100', '01000'],
            '_' => ['00000', '00000', '00000', '00000', '00000', '00000', '11111'],
            '-' => ['00000', '00000', '00000', '11111', '00000', '00000', '00000'],
        ];
    }
}
