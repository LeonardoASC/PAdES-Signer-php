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

        $catalogBody = $this->ensureEtsiExtension($catalogBody);

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

    public function ensureEtsiExtension(string $catalogBody): string
    {
        if (str_contains($catalogBody, '/ESIC')) {
            return $catalogBody;
        }

        $extension = "/ESIC <<\n"
            . "/Type /DeveloperExtensions\n"
            . "/BaseVersion /1.7\n"
            . "/ExtensionLevel 1\n"
            . ">>\n";

        if (str_contains($catalogBody, '/Extensions')) {
            $updated = preg_replace(
                '/\/Extensions\s*<</',
                "/Extensions <<\n{$extension}",
                $catalogBody,
                1
            );

            if ($updated === null) {
                throw new RuntimeException('Nao foi possivel atualizar /Extensions do Catalog.');
            }

            return $updated;
        }

        $updated = preg_replace(
            '/>>\s*$/',
            "/Extensions <<\n{$extension}>>\n>>",
            $catalogBody
        );

        if ($updated === null) {
            throw new RuntimeException('Nao foi possivel adicionar /Extensions ao Catalog.');
        }

        return $updated;
    }

    public function addDss(
        string $catalogBody,
        int $dssObjectNumber
    ): string {
        if (str_contains($catalogBody, '/DSS')) {
            throw new RuntimeException('O Catalog ja possui /DSS.');
        }

        $updated = preg_replace(
            '/>>\s*$/',
            "/DSS {$dssObjectNumber} 0 R\n>>",
            $catalogBody
        );

        if ($updated === null) {
            throw new RuntimeException('Nao foi possivel atualizar o Catalog.');
        }

        return $updated;
    }
}
