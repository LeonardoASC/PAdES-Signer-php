<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfAcroFormUpdater
{
    public function addSignatureField(
        string $acroFormBody,
        int $widgetObjectNumber
    ): string {
        if (preg_match('/\/Fields\s*\[([^\]]*)\]/s', $acroFormBody)) {
            $updated = preg_replace(
                '/\/Fields\s*\[([^\]]*)\]/s',
                "/Fields [$1 {$widgetObjectNumber} 0 R]",
                $acroFormBody,
                1
            );

            if ($updated === null) {
                throw new RuntimeException('Nao foi possivel atualizar /Fields do AcroForm.');
            }
        } else {
            $updated = preg_replace(
                '/>>\s*$/',
                "/Fields [{$widgetObjectNumber} 0 R]\n>>",
                $acroFormBody,
                1
            );

            if ($updated === null) {
                throw new RuntimeException('Nao foi possivel adicionar /Fields ao AcroForm.');
            }
        }

        if (str_contains($updated, '/SigFlags')) {
            return $updated;
        }

        $withSigFlags = preg_replace(
            '/>>\s*$/',
            "/SigFlags 3\n>>",
            $updated,
            1
        );

        if ($withSigFlags === null) {
            throw new RuntimeException('Nao foi possivel adicionar /SigFlags ao AcroForm.');
        }

        return $withSigFlags;
    }
}
