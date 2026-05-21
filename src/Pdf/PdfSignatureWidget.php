<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureWidget
{
    /**
     * @param array{0:int|float, 1:int|float, 2:int|float, 3:int|float} $rect
     */
    public function build(
        int $signatureObjectNumber,
        int $pageObjectNumber = 3,
        array $rect = [0, 0, 0, 0],
        int $flags = 4,
        ?int $appearanceObjectNumber = null,
        string $fieldName = 'Signature1'
    ): string {
        $widget = "<<\n"
            . "/Type /Annot\n"
            . "/Subtype /Widget\n"
            . "/FT /Sig\n"
            . "/Rect [" . $this->formatRect($rect) . "]\n"
            . "/V {$signatureObjectNumber} 0 R\n"
            . "/T " . $this->pdfString($fieldName) . "\n"
            . "/F {$flags}\n"
            . "/P {$pageObjectNumber} 0 R\n";

        if ($appearanceObjectNumber !== null) {
            $widget .= "/AP <<\n"
                . "/N {$appearanceObjectNumber} 0 R\n"
                . ">>\n";
        }

        return $widget . ">>";
    }

    /**
     * @param array{0:int|float, 1:int|float, 2:int|float, 3:int|float} $rect
     */
    private function formatRect(array $rect): string
    {
        return implode(
            ' ',
            array_map(
                static fn (int|float $value): string => is_float($value)
                    ? rtrim(rtrim(sprintf('%.6F', $value), '0'), '.')
                    : (string) $value,
                $rect
            )
        );
    }

    private function pdfString(string $value): string
    {
        return '(' . str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $value
        ) . ')';
    }
}
