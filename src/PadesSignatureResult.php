<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use DateTimeImmutable;

final readonly class PadesSignatureResult
{
    /**
     * @param array<string> $warnings
     */
    public function __construct(
        public string $inputPdf,
        public string $outputPdf,
        public string $profile,
        public string $sha256,
        public int $size,
        public DateTimeImmutable $signedAt,
        public bool $timestamped,
        public bool $visible,
        public bool $signaturePageAppended,
        public ?string $signatureName = null,
        public ?string $signatureFieldName = null,
        public array $warnings = []
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'inputPdf' => $this->inputPdf,
            'outputPdf' => $this->outputPdf,
            'profile' => $this->profile,
            'sha256' => $this->sha256,
            'size' => $this->size,
            'signedAt' => $this->signedAt->format(DATE_ATOM),
            'timestamped' => $this->timestamped,
            'visible' => $this->visible,
            'signaturePageAppended' => $this->signaturePageAppended,
            'signatureName' => $this->signatureName,
            'signatureFieldName' => $this->signatureFieldName,
            'warnings' => $this->warnings,
        ];
    }
}
