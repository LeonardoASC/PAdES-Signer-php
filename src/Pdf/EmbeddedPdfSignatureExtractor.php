<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use RuntimeException;

final readonly class EmbeddedPdfSignatureExtractor
{
    /**
     * @return list<EmbeddedPdfSignature>
     */
    public function extractAll(string $pdfContent): array
    {
        $structure = (new PdfStructuralParser())->parse($pdfContent);
        $reader = new PdfDictionaryReader();
        $signatures = [];
        $seenSignatureObjects = [];

        foreach ($structure->objects as $object) {
            $signatureReference = $reader->getReference($object->body, 'V');

            if ($signatureReference === null || ! $this->isSignatureField($object->body, $reader)) {
                continue;
            }

            $signatureObject = $structure->getObjectByReference($signatureReference);
            $seenSignatureObjects[$signatureObject->number] = true;
            $signatures[] = $this->buildSignature(
                pdfContent: $pdfContent,
                fieldObjectNumber: $object->number,
                fieldDictionary: $object->body,
                signatureObjectNumber: $signatureObject->number,
                signatureDictionary: $signatureObject->body,
                fieldName: $this->fieldName($object->body, $reader),
                reader: $reader
            );
        }

        foreach ($structure->objects as $object) {
            if (isset($seenSignatureObjects[$object->number]) || ! $this->isSignatureDictionary($object->body, $reader)) {
                continue;
            }

            $signatures[] = $this->buildSignature(
                pdfContent: $pdfContent,
                fieldObjectNumber: 0,
                fieldDictionary: '',
                signatureObjectNumber: $object->number,
                signatureDictionary: $object->body,
                fieldName: null,
                reader: $reader
            );
        }

        if ($signatures === []) {
            throw new RuntimeException('Nenhuma assinatura PDF embutida foi encontrada.');
        }

        return $signatures;
    }

    public function extractFirst(string $pdfContent): EmbeddedPdfSignature
    {
        return $this->extractAll($pdfContent)[0];
    }

    private function buildSignature(
        string $pdfContent,
        int $fieldObjectNumber,
        string $fieldDictionary,
        int $signatureObjectNumber,
        string $signatureDictionary,
        ?string $fieldName,
        PdfDictionaryReader $reader
    ): EmbeddedPdfSignature {
        $byteRangeValues = $reader->getIntegerArray($signatureDictionary, 'ByteRange');

        if (count($byteRangeValues) !== 4) {
            throw new RuntimeException('Dicionario /Sig sem /ByteRange valido.');
        }

        $byteRange = new ByteRange(...$byteRangeValues);
        $contentsValue = trim($reader->getValue($signatureDictionary, 'Contents') ?? '');

        if (! str_starts_with($contentsValue, '<') || ! str_ends_with($contentsValue, '>')) {
            throw new RuntimeException('Dicionario /Sig sem /Contents hexadecimal.');
        }

        $contentsHex = preg_replace('/\s+/', '', substr($contentsValue, 1, -1)) ?? '';
        $contentsDer = hex2bin($contentsHex);

        if ($contentsDer === false) {
            throw new RuntimeException('/Contents da assinatura contem hexadecimal invalido.');
        }

        $signedData = (new ByteRangeCalculator())->extractSignedData($pdfContent, $byteRange);
        $signedRevisionEnd = $byteRange->start2 + $byteRange->length2;

        return new EmbeddedPdfSignature(
            fieldObjectNumber: $fieldObjectNumber,
            signatureObjectNumber: $signatureObjectNumber,
            fieldName: $fieldName,
            fieldDictionary: $fieldDictionary,
            signatureDictionary: $signatureDictionary,
            byteRange: $byteRange,
            contentsHex: $contentsHex,
            contentsDer: $contentsDer,
            contentsDerWithoutPadding: rtrim($contentsDer, "\x00"),
            signedData: $signedData,
            signedRevisionEnd: $signedRevisionEnd,
            coversWholeDocument: $signedRevisionEnd === strlen($pdfContent),
            uncoveredRanges: $this->uncoveredRanges($pdfContent, $byteRange)
        );
    }

    private function isSignatureField(string $dictionary, PdfDictionaryReader $reader): bool
    {
        return $reader->hasNameValue($dictionary, 'FT', 'Sig')
            || $reader->hasNameValue($dictionary, 'Subtype', 'Widget');
    }

    private function isSignatureDictionary(string $dictionary, PdfDictionaryReader $reader): bool
    {
        return $reader->hasNameValue($dictionary, 'Type', 'Sig')
            || (
                $reader->getValue($dictionary, 'ByteRange') !== null
                && $reader->getValue($dictionary, 'Contents') !== null
            );
    }

    private function fieldName(string $dictionary, PdfDictionaryReader $reader): ?string
    {
        $value = trim($reader->getValue($dictionary, 'T') ?? '');

        if (! str_starts_with($value, '(') || ! str_ends_with($value, ')')) {
            return null;
        }

        return str_replace(
            ['\\)', '\\(', '\\\\'],
            [')', '(', '\\'],
            substr($value, 1, -1)
        );
    }

    /**
     * @return list<array{start:int,length:int,kind:string}>
     */
    private function uncoveredRanges(string $pdfContent, ByteRange $byteRange): array
    {
        $ranges = [];
        $contentsStart = $byteRange->start1 + $byteRange->length1;
        $contentsLength = $byteRange->start2 - $contentsStart;

        if ($contentsLength > 0) {
            $ranges[] = [
                'start' => $contentsStart,
                'length' => $contentsLength,
                'kind' => 'signature_contents',
            ];
        }

        $signedRevisionEnd = $byteRange->start2 + $byteRange->length2;
        $tailLength = strlen($pdfContent) - $signedRevisionEnd;

        if ($tailLength > 0) {
            $ranges[] = [
                'start' => $signedRevisionEnd,
                'length' => $tailLength,
                'kind' => 'unsigned_tail',
            ];
        }

        return $ranges;
    }
}
