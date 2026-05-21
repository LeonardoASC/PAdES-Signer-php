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
        try {
            return (new PdfDictionaryUpdater())->appendReferenceToArray(
                dictionary: $pageBody,
                name: 'Annots',
                objectNumber: $widgetObjectNumber
            );
        } catch (RuntimeException $exception) {
            throw new RuntimeException(
                'Nao foi possivel atualizar /Annots do objeto /Page.',
                previous: $exception
            );
        }
    }
}
