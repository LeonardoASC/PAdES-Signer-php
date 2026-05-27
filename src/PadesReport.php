<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

final readonly class PadesReport
{
    public function __construct(
        private PadesValidator $validator = new PadesValidator()
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forFile(string $pdfPath): array
    {
        return $this->validator->validateFile($pdfPath)->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function forPdf(string $pdfContent): array
    {
        return $this->validator->validate($pdfContent)->toArray();
    }
}
