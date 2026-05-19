<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf\Dss;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;

final readonly class PdfDssObjectBuilder
{
    /**
     * @return array{
     *     dssObjectNumber:int,
     *     objects:array<int, string>,
     *     certObjectNumbers:array<int>,
     *     ocspObjectNumbers:array<int>,
     *     crlObjectNumbers:array<int>
     * }
     */
    public function build(
        int $firstObjectNumber,
        LtvValidationMaterial $material,
        string $vriHash
    ): array {
        if ($firstObjectNumber < 1) {
            throw new InvalidArgumentException('First DSS object number must be positive.');
        }

        $objects = [];
        $nextObjectNumber = $firstObjectNumber;

        $certObjectNumbers = $this->appendDerStreamObjects(
            objects: $objects,
            nextObjectNumber: $nextObjectNumber,
            derObjects: $material->certificatesDer
        );

        $ocspObjectNumbers = $this->appendDerStreamObjects(
            objects: $objects,
            nextObjectNumber: $nextObjectNumber,
            derObjects: $material->ocspResponsesDer
        );

        $crlObjectNumbers = $this->appendDerStreamObjects(
            objects: $objects,
            nextObjectNumber: $nextObjectNumber,
            derObjects: $material->crlsDer
        );

        $certReferences = $this->references($certObjectNumbers);
        $ocspReferences = $this->references($ocspObjectNumbers);
        $crlReferences = $this->references($crlObjectNumbers);

        $vriDictionary = (new PdfVriDictionary())
            ->build(
                signatureHash: $vriHash,
                certReferences: $certReferences,
                ocspReferences: $ocspReferences,
                crlReferences: $crlReferences
            );

        $dssObjectNumber = $nextObjectNumber;

        $objects[$dssObjectNumber] = (new PdfDssDictionary())
            ->build(
                certReferences: $certReferences,
                ocspReferences: $ocspReferences,
                crlReferences: $crlReferences,
                vriDictionary: $vriDictionary
            );

        return [
            'dssObjectNumber' => $dssObjectNumber,
            'objects' => $objects,
            'certObjectNumbers' => $certObjectNumbers,
            'ocspObjectNumbers' => $ocspObjectNumbers,
            'crlObjectNumbers' => $crlObjectNumbers,
        ];
    }

    /**
     * @param array<int, string> $objects
     * @param array<string> $derObjects
     * @return array<int>
     */
    private function appendDerStreamObjects(
        array &$objects,
        int &$nextObjectNumber,
        array $derObjects
    ): array {
        $objectNumbers = [];

        foreach ($derObjects as $derObject) {
            $objectNumber = $nextObjectNumber++;

            $objects[$objectNumber] = $this->buildDerStreamObject($derObject);
            $objectNumbers[] = $objectNumber;
        }

        return $objectNumbers;
    }

    private function buildDerStreamObject(string $der): string
    {
        return "<<\n"
            . "/Length " . strlen($der) . "\n"
            . ">>\n"
            . "stream\n"
            . $der . "\n"
            . "endstream";
    }

    /**
     * @param array<int> $objectNumbers
     * @return array<string>
     */
    private function references(array $objectNumbers): array
    {
        return array_map(
            static fn (int $objectNumber): string => "{$objectNumber} 0 R",
            $objectNumbers
        );
    }
}
