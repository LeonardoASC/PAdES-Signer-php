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
        $updater = new PdfDictionaryUpdater();

        try {
            $updated = $updater->appendReferenceToArray(
                dictionary: $acroFormBody,
                name: 'Fields',
                objectNumber: $widgetObjectNumber
            );

            return $updater->ensureNameInteger($updated, 'SigFlags', 3);
        } catch (RuntimeException $exception) {
            throw new RuntimeException(
                'Nao foi possivel atualizar o AcroForm com o campo de assinatura.',
                previous: $exception
            );
        }
    }
}
