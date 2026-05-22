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
        $extension = "<<\n"
            . "/Type /DeveloperExtensions\n"
            . "/BaseVersion /1.7\n"
            . "/ExtensionLevel 1\n"
            . ">>";

        return (new PdfDictionaryUpdater())->ensureDictionaryEntry(
            dictionary: $catalogBody,
            dictionaryName: 'Extensions',
            entryName: 'ESIC',
            entryBody: $extension
        );
    }

    public function addDocMdpPermission(
        string $catalogBody,
        int $signatureObjectNumber
    ): string {
        if (preg_match('/\/Perms\s*<<(?:(?!>>).)*\/DocMDP\b/s', $catalogBody) === 1) {
            throw new RuntimeException('O Catalog ja possui permissao DocMDP.');
        }

        return (new PdfDictionaryUpdater())->ensureDictionaryEntry(
            dictionary: $catalogBody,
            dictionaryName: 'Perms',
            entryName: 'DocMDP',
            entryBody: "{$signatureObjectNumber} 0 R"
        );
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

    public function setDss(
        string $catalogBody,
        int $dssObjectNumber
    ): string {
        if (preg_match('/\/DSS\s+\d+\s+0\s+R/', $catalogBody) === 1) {
            $updated = preg_replace(
                '/\/DSS\s+\d+\s+0\s+R/',
                "/DSS {$dssObjectNumber} 0 R",
                $catalogBody,
                1
            );

            if ($updated === null) {
                throw new RuntimeException('Nao foi possivel substituir /DSS no Catalog.');
            }

            return $updated;
        }

        return $this->addDss($catalogBody, $dssObjectNumber);
    }
}
