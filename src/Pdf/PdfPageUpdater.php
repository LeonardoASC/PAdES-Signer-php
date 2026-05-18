<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfPageUpdater
{
    public function addAnnotation(
        string $pageBody,
        int $widgetObjectNumber
    ): string {
        if (preg_match('/\/Annots\s*\[([^\]]*)\]/s', $pageBody)) {
            $updated = preg_replace(
                '/\/Annots\s*\[([^\]]*)\]/s',
                "/Annots [$1 {$widgetObjectNumber} 0 R]",
                $pageBody,
                1
            );

            if ($updated === null) {
                throw new RuntimeException('Não foi possível atualizar /Annots existente.');
            }

            return $updated;
        }

        $updated = preg_replace(
            '/>>\s*$/',
            "/Annots [{$widgetObjectNumber} 0 R]\n>>",
            $pageBody
        );

        if ($updated === null) {
            throw new RuntimeException('Não foi possível atualizar o objeto /Page.');
        }

        return $updated;
    }
}
