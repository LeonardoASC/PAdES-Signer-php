<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

final readonly class AuthorityInfoAccessParser
{
    public function ocspUrl(
        string $authorityInfoAccess
    ): ?string {
        return $this->ocspUrls($authorityInfoAccess)[0]
            ?? null;
    }

    /**
     * @return array<string>
     */
    public function ocspUrls(string $authorityInfoAccess): array
    {
        return $this->urls(
            authorityInfoAccess: $authorityInfoAccess,
            label: 'OCSP'
        );
    }

    public function caIssuersUrl(
        string $authorityInfoAccess
    ): ?string {
        return $this->caIssuersUrls($authorityInfoAccess)[0]
            ?? null;
    }

    /**
     * @return array<string>
     */
    public function caIssuersUrls(string $authorityInfoAccess): array
    {
        return $this->urls(
            authorityInfoAccess: $authorityInfoAccess,
            label: 'CA Issuers'
        );
    }

    /**
     * @return array<string>
     */
    private function urls(
        string $authorityInfoAccess,
        string $label
    ): array {
        $urls = [];
        $normalizedLabel = $this->normalizeLabel($label);

        foreach (preg_split('/\R+/', $authorityInfoAccess) ?: [] as $line) {
            if (! str_contains($this->normalizeLabel($line), $normalizedLabel)) {
                continue;
            }

            if (preg_match_all('/https?:\/\/[^\s,;<>"]+/i', $line, $matches)) {
                array_push($urls, ...$matches[0]);
            }
        }

        if ($urls === [] && preg_match_all(
            '/' . preg_quote($label, '/') . '[^\r\n]*?(https?:\/\/[^\s,;<>"]+)/i',
            $authorityInfoAccess,
            $matches
        )) {
            $urls = $matches[1];
        }

        if ($urls === []) {
            return [];
        }

        return array_values(array_unique(array_map(
            static fn (string $url): string => trim($url),
            $urls
        )));
    }

    private function normalizeLabel(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]+/i', '', $value));
    }
}
