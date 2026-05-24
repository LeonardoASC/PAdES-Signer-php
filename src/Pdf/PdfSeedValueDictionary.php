<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;

final readonly class PdfSeedValueDictionary
{
    /**
     * @param array<string> $filters
     * @param array<string> $subFilters
     * @param array<string> $digestMethods
     * @param array<string> $reasons
     * @param array<string> $certificateSubjects
     */
    public function __construct(
        public array $filters = [],
        public array $subFilters = [],
        public array $digestMethods = [],
        public array $reasons = [],
        public array $certificateSubjects = []
    ) {}

    public static function fromDictionary(string $dictionary): self
    {
        $reader = new PdfDictionaryReader();

        return new self(
            filters: self::nameArray($reader->getValue($dictionary, 'Filter')),
            subFilters: self::nameArray($reader->getValue($dictionary, 'SubFilter')),
            digestMethods: self::nameArray($reader->getValue($dictionary, 'DigestMethod')),
            reasons: self::stringArray($reader->getValue($dictionary, 'Reasons')),
            certificateSubjects: self::certificateSubjects($reader->getValue($dictionary, 'Cert'))
        );
    }

    public function validate(
        SignatureAlgorithmPolicy $algorithmPolicy,
        string $signatureReason,
        ?string $certificatePem = null
    ): void {
        if ($this->filters !== [] && ! in_array('Adobe.PPKLite', $this->filters, true)) {
            throw new InvalidArgumentException('Seed Value Dictionary nao permite /Filter /Adobe.PPKLite.');
        }

        if ($this->subFilters !== [] && ! in_array('ETSI.CAdES.detached', $this->subFilters, true)) {
            throw new InvalidArgumentException('Seed Value Dictionary nao permite /SubFilter /ETSI.CAdES.detached.');
        }

        if ($this->digestMethods !== [] && ! in_array($this->pdfDigestName($algorithmPolicy), $this->digestMethods, true)) {
            throw new InvalidArgumentException('Seed Value Dictionary nao permite o algoritmo de hash configurado.');
        }

        if ($this->reasons !== [] && ! in_array($signatureReason, $this->reasons, true)) {
            throw new InvalidArgumentException('Seed Value Dictionary nao permite o motivo de assinatura configurado.');
        }

        if ($this->certificateSubjects !== [] && ! $this->certificateMatches($certificatePem)) {
            throw new InvalidArgumentException('Seed Value Dictionary nao permite o certificado configurado.');
        }
    }

    private function pdfDigestName(SignatureAlgorithmPolicy $policy): string
    {
        return match ($policy->hashAlgorithm) {
            SignatureAlgorithmPolicy::HASH_SHA256 => 'SHA256',
            SignatureAlgorithmPolicy::HASH_SHA384 => 'SHA384',
            SignatureAlgorithmPolicy::HASH_SHA512 => 'SHA512',
            default => strtoupper($policy->hashAlgorithm),
        };
    }

    private function certificateMatches(?string $certificatePem): bool
    {
        if ($certificatePem === null) {
            return false;
        }

        $parsed = openssl_x509_parse($certificatePem);

        if (! is_array($parsed)) {
            return false;
        }

        $subject = $parsed['subject'] ?? [];
        $subjectText = json_encode($subject);

        if (! is_string($subjectText)) {
            return false;
        }

        foreach ($this->certificateSubjects as $allowed) {
            if (str_contains($subjectText, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string>
     */
    private static function nameArray(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        preg_match_all('/\/([A-Za-z0-9_.-]+)/', $value, $matches);

        return array_values(array_unique($matches[1]));
    }

    /**
     * @return array<string>
     */
    private static function stringArray(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        preg_match_all('/\((.*?)(?<!\\\\)\)/s', $value, $matches);

        return array_map(
            static fn (string $item): string => str_replace(['\\)', '\\(', '\\\\'], [')', '(', '\\'], $item),
            $matches[1]
        );
    }

    /**
     * @return array<string>
     */
    private static function certificateSubjects(?string $value): array
    {
        if ($value === null) {
            return [];
        }

        if (preg_match('/\/Subject\s*\[(.*?)\]/s', $value, $matches) !== 1) {
            return [];
        }

        return self::stringArray('[' . $matches[1] . ']');
    }
}
