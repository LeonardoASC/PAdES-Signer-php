<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureStructureValidator
{
    public function validate(string $pdfContent): array
    {
        $objects = $this->objects($pdfContent);
        $widgetObjectNumber = null;
        $signatureObjectNumber = null;

        foreach ($objects as $objectNumber => $body) {
            if (
                ! str_contains($body, '/Subtype /Widget')
                || ! str_contains($body, '/FT /Sig')
            ) {
                continue;
            }

            $widgetObjectNumber = (string) $objectNumber;

            if (preg_match('/\/V\s+(\d+)\s+0\s+R/', $body, $matches)) {
                $signatureObjectNumber = $matches[1];
            }

            break;
        }

        return [
            'has_signature_object' => $signatureObjectNumber !== null
                && isset($objects[(int) $signatureObjectNumber])
                && str_contains($objects[(int) $signatureObjectNumber], '/Type /Sig'),

            'has_signature_dictionary' => str_contains(
                $pdfContent,
                '/Type /Sig'
            ),

            'has_pades_subfilter' => str_contains(
                $pdfContent,
                '/SubFilter /ETSI.CAdES.detached'
            ),

            'has_byte_range' => preg_match(
                '/\/ByteRange\s*\[\d+\s+\d+\s+\d+\s+\d+\]/',
                $pdfContent
            ) === 1,

            'has_contents' => preg_match(
                '/\/Contents\s*<[0-9A-F]+>/',
                $pdfContent
            ) === 1,

            'has_acroform' => str_contains(
                $pdfContent,
                '/AcroForm'
            ),

            'has_fields' => preg_match(
                '/\/Fields\s*\[(?:(?!\]).)*\d+\s+0\s+R(?:(?!\]).)*\]/s',
                $pdfContent
            ) === 1,

            'has_widget' => $widgetObjectNumber !== null,

            'has_signature_field' => str_contains(
                $pdfContent,
                '/FT /Sig'
            ),

            'has_signature_value_reference' => $signatureObjectNumber !== null,

            'has_annots' => str_contains(
                $pdfContent,
                '/Annots'
            ),

            'has_widget_in_acroform_fields' => $widgetObjectNumber !== null
                && preg_match(
                    '/\/Fields\s*\[(?:(?!\]).)*\b' . preg_quote($widgetObjectNumber, '/') . '\s+0\s+R(?:(?!\]).)*\]/s',
                    $pdfContent
                ) === 1,

            'has_widget_in_page_annots' => $widgetObjectNumber !== null
                && preg_match(
                    '/\/Annots\s*\[(?:(?!\]).)*\b' . preg_quote($widgetObjectNumber, '/') . '\s+0\s+R(?:(?!\]).)*\]/s',
                    $pdfContent
                ) === 1,

            'has_root_in_incremental_trailer' => preg_match(
                '/trailer\s*<<(?:(?!>>).)*\/Root\s+\d+\s+\d+\s+R(?:(?!>>).)*\/Prev/s',
                $pdfContent
            ) === 1,

            'has_incremental_xref' => preg_match(
                '/xref\s+(?:0\s+1\s+0000000000\s+65535\s+f\s+)?\d+\s+\d+\s+\d{10}\s+\d{5}\s+n\s+/s',
                $pdfContent
            ) === 1,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function objects(string $pdfContent): array
    {
        if (! preg_match_all(
            '/(\d+)\s+0\s+obj\s*(.*?)\s*endobj/s',
            $pdfContent,
            $matches,
            PREG_SET_ORDER
        )) {
            return [];
        }

        $objects = [];

        foreach ($matches as $match) {
            $objects[(int) $match[1]] = $match[2];
        }

        return $objects;
    }
}
