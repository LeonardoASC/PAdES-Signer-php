<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class PdfSignatureStructureValidator
{
    public function validate(string $pdfContent): array
    {
        return [
            'has_signature_object' => str_contains($pdfContent, '/Type /Sig'),

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

            'has_widget' => str_contains(
                $pdfContent,
                '/Subtype /Widget'
            ),

            'has_annots' => str_contains(
                $pdfContent,
                '/Annots'
            ),

            'has_root_in_incremental_trailer' => preg_match(
                '/trailer\s*<<(?:(?!>>).)*\/Root\s+\d+\s+\d+\s+R(?:(?!>>).)*\/Prev/s',
                $pdfContent
            ) === 1,
        ];
    }
}