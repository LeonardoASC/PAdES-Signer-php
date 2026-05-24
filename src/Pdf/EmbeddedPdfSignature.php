<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

final readonly class EmbeddedPdfSignature
{
    /**
     * @param list<array{start:int,length:int,kind:string}> $uncoveredRanges
     */
    public function __construct(
        public int $fieldObjectNumber,
        public int $signatureObjectNumber,
        public ?string $fieldName,
        public string $fieldDictionary,
        public string $signatureDictionary,
        public ByteRange $byteRange,
        public string $contentsHex,
        public string $contentsDer,
        public string $contentsDerWithoutPadding,
        public string $signedData,
        public int $signedRevisionEnd,
        public bool $coversWholeDocument,
        public array $uncoveredRanges
    ) {}

    public function digest(string $algorithm = 'sha256'): string
    {
        return hash($algorithm, $this->signedData, binary: true);
    }

    public function digestHex(string $algorithm = 'sha256'): string
    {
        return strtoupper(hash($algorithm, $this->signedData));
    }
}
