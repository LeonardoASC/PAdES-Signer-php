<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use InvalidArgumentException;
use NihilLabs\Pades\Validation\PadesBbValidator;
use NihilLabs\Pades\Validation\PadesBtValidator;
use NihilLabs\Pades\Validation\PadesLtValidator;
use NihilLabs\Pades\Validation\PadesLtaValidator;
use NihilLabs\Pades\Validation\PadesProfileValidationResult;

final readonly class PadesValidator
{
    public function __construct(
        private PadesBbValidator $bbValidator = new PadesBbValidator(),
        private PadesBtValidator $btValidator = new PadesBtValidator(),
        private PadesLtValidator $ltValidator = new PadesLtValidator(),
        private PadesLtaValidator $ltaValidator = new PadesLtaValidator()
    ) {}

    public function validateFile(string $pdfPath): PadesValidationResult
    {
        $pdfContent = file_get_contents($pdfPath);

        if ($pdfContent === false) {
            throw new InvalidArgumentException("PDF nao encontrado ou ilegivel: {$pdfPath}");
        }

        return $this->validate($pdfContent);
    }

    public function validate(string $pdfContent): PadesValidationResult
    {
        $results = $this->validateAllInternal($pdfContent);
        $best = $this->bestResult($results);

        return $this->publicResult($best, $results);
    }

    public function validateProfileFile(string $pdfPath, string $profile): PadesValidationResult
    {
        $pdfContent = file_get_contents($pdfPath);

        if ($pdfContent === false) {
            throw new InvalidArgumentException("PDF nao encontrado ou ilegivel: {$pdfPath}");
        }

        return $this->validateProfile($pdfContent, $profile);
    }

    public function validateProfile(string $pdfContent, string $profile): PadesValidationResult
    {
        return $this->publicResult($this->validateInternalProfile($pdfContent, $profile));
    }

    /**
     * @return array<string, PadesValidationResult>
     */
    public function validateAllFile(string $pdfPath): array
    {
        $pdfContent = file_get_contents($pdfPath);

        if ($pdfContent === false) {
            throw new InvalidArgumentException("PDF nao encontrado ou ilegivel: {$pdfPath}");
        }

        return $this->validateAll($pdfContent);
    }

    /**
     * @return array<string, PadesValidationResult>
     */
    public function validateAll(string $pdfContent): array
    {
        $results = $this->validateAllInternal($pdfContent);
        $publicResults = [];

        foreach ($results as $profile => $result) {
            $publicResults[$profile] = $this->publicResult($result);
        }

        return $publicResults;
    }

    /**
     * @return array<string, PadesProfileValidationResult>
     */
    private function validateAllInternal(string $pdfContent): array
    {
        return [
            PadesProfile::B_B => $this->validateInternalProfile($pdfContent, PadesProfile::B_B),
            PadesProfile::B_T => $this->validateInternalProfile($pdfContent, PadesProfile::B_T),
            PadesProfile::B_LT => $this->validateInternalProfile($pdfContent, PadesProfile::B_LT),
            PadesProfile::B_LTA => $this->validateInternalProfile($pdfContent, PadesProfile::B_LTA),
        ];
    }

    /**
     * @param array<string, PadesProfileValidationResult> $results
     */
    private function bestResult(array $results): PadesProfileValidationResult
    {
        foreach ([
            PadesProfile::B_LTA,
            PadesProfile::B_LT,
            PadesProfile::B_T,
            PadesProfile::B_B,
        ] as $profile) {
            if ($results[$profile]->valid) {
                return $results[$profile];
            }
        }

        return $results[PadesProfile::B_B];
    }

    private function validateInternalProfile(string $pdfContent, string $profile): PadesProfileValidationResult
    {
        return match ($profile) {
            PadesProfile::B_B => $this->bbValidator->validatePdf($pdfContent),
            PadesProfile::B_T => $this->btValidator->validatePdf($pdfContent),
            PadesProfile::B_LT => $this->ltValidator->validatePdf($pdfContent),
            PadesProfile::B_LTA => $this->ltaValidator->validatePdf($pdfContent),
            default => throw new InvalidArgumentException("Perfil PAdES invalido: {$profile}"),
        };
    }

    /**
     * @param array<string, PadesProfileValidationResult> $profiles
     */
    private function publicResult(
        PadesProfileValidationResult $result,
        array $profiles = []
    ): PadesValidationResult {
        return new PadesValidationResult(
            profile: $result->profile,
            valid: $result->valid,
            checks: $result->checks,
            messages: $result->messages,
            profiles: $this->profileDetails($profiles)
        );
    }

    /**
     * @param array<string, PadesProfileValidationResult> $results
     * @return array<string, array{valid:bool,checks:array<string,bool>,messages:array<string>}>
     */
    private function profileDetails(array $results): array
    {
        $details = [];

        foreach ($results as $profile => $result) {
            $details[$profile] = [
                'valid' => $result->valid,
                'checks' => $result->checks,
                'messages' => $result->messages,
            ];
        }

        return $details;
    }
}
