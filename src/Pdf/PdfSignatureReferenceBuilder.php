<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;

final readonly class PdfSignatureReferenceBuilder
{
    /**
     * @param array<string> $lockedFieldNames
     */
    public function build(
        int $catalogObjectNumber,
        ?int $certificationPermission = null,
        array $lockedFieldNames = [],
        string $fieldLockAction = 'Include'
    ): string {
        $references = [];

        if ($certificationPermission !== null) {
            $references[] = $this->docMdpReference(
                catalogObjectNumber: $catalogObjectNumber,
                permission: $certificationPermission
            );
        }

        if ($lockedFieldNames !== [] || $fieldLockAction === 'All') {
            $references[] = $this->fieldMdpReference(
                catalogObjectNumber: $catalogObjectNumber,
                fieldNames: $lockedFieldNames,
                action: $fieldLockAction
            );
        }

        if ($references === []) {
            return '';
        }

        return "/Reference [\n" . implode("\n", $references) . "\n]\n";
    }

    private function docMdpReference(int $catalogObjectNumber, int $permission): string
    {
        if (! in_array($permission, [1, 2, 3], true)) {
            throw new InvalidArgumentException('DocMDP permission must be 1, 2, or 3.');
        }

        return "<<\n"
            . "/Type /SigRef\n"
            . "/TransformMethod /DocMDP\n"
            . "/TransformParams <<\n"
            . "/Type /TransformParams\n"
            . "/P {$permission}\n"
            . "/V /1.2\n"
            . ">>\n"
            . "/Data {$catalogObjectNumber} 0 R\n"
            . ">>";
    }

    /**
     * @param array<string> $fieldNames
     */
    private function fieldMdpReference(int $catalogObjectNumber, array $fieldNames, string $action): string
    {
        if (! in_array($action, ['All', 'Include', 'Exclude'], true)) {
            throw new InvalidArgumentException('FieldMDP action must be All, Include, or Exclude.');
        }

        if ($action !== 'All' && $fieldNames === []) {
            throw new InvalidArgumentException('FieldMDP Include/Exclude actions require field names.');
        }

        $transformParams = "<<\n"
            . "/Type /TransformParams\n"
            . "/Action /{$action}\n";

        if ($action !== 'All') {
            $transformParams .= "/Fields ["
                . implode(' ', array_map($this->pdfString(...), $fieldNames))
                . "]\n";
        }

        $transformParams .= "/V /1.2\n>>";

        return "<<\n"
            . "/Type /SigRef\n"
            . "/TransformMethod /FieldMDP\n"
            . "/TransformParams {$transformParams}\n"
            . "/Data {$catalogObjectNumber} 0 R\n"
            . ">>";
    }

    private function pdfString(string $value): string
    {
        return '(' . str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $value
        ) . ')';
    }
}
