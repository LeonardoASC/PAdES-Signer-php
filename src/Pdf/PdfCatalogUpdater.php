<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class PdfCatalogUpdater
{
    public function addAcroForm(
        string $catalogBody,
        int $acroFormObjectNumber
    ): string {
        if (str_contains($catalogBody, '/AcroForm')) {
            throw new RuntimeException('O Catalog já possui /AcroForm.');
        }

        $updated = preg_replace(
            '/>>\s*$/',
            "/AcroForm {$acroFormObjectNumber} 0 R\n>>",
            $catalogBody
        );

        if ($updated === null) {
            throw new RuntimeException('Não foi possível atualizar o Catalog.');
        }

        return $updated;
    }
}