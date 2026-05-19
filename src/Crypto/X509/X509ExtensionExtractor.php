<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class X509ExtensionExtractor
{
    /**
     * @return array<string, mixed>
     */
    public function extract(string $certificatePem): array
    {
        $parsed = openssl_x509_parse(
            $certificatePem
        );

        if ($parsed === false) {
            return [];
        }

        return $parsed['extensions'] ?? [];
    }

    public function authorityInfoAccess(
        string $certificatePem
    ): ?string {
        $extensions = $this->extract(
            $certificatePem
        );

        $parsed = $this->extensionValue(
            extensions: $extensions,
            names: [
                'authorityInfoAccess',
                'Authority Information Access',
                '1.3.6.1.5.5.7.1.1',
            ]
        );

        $fromDer = $this->authorityInfoAccessFromDer($certificatePem);

        if ($parsed !== null && $fromDer !== null && $parsed !== $fromDer) {
            return $parsed . "\n" . $fromDer;
        }

        $combined = $parsed ?? $fromDer;
        $fromScan = $this->authorityInfoAccessByOidScan($certificatePem);

        if ($combined !== null && $fromScan !== null && $combined !== $fromScan) {
            return $combined . "\n" . $fromScan;
        }

        return $combined ?? $fromScan;
    }

    public function crlDistributionPoints(
        string $certificatePem
    ): ?string {
        $extensions = $this->extract(
            $certificatePem
        );

        $parsed = $this->extensionValue(
            extensions: $extensions,
            names: [
                'crlDistributionPoints',
                'CRL Distribution Points',
                '2.5.29.31',
            ]
        );

        $fromScan = $this->crlDistributionPointsByOidScan($certificatePem);

        if ($parsed !== null && $fromScan !== null && $parsed !== $fromScan) {
            return $parsed . "\n" . $fromScan;
        }

        return $parsed ?? $fromScan;
    }

    /**
     * @param array<string, mixed> $extensions
     * @param array<string> $names
     */
    private function extensionValue(
        array $extensions,
        array $names
    ): ?string {
        $normalizedNames = array_map(
            fn (string $name): string => $this->normalizeExtensionName($name),
            $names
        );

        foreach ($extensions as $name => $value) {
            if (! in_array($this->normalizeExtensionName((string) $name), $normalizedNames, true)) {
                continue;
            }

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function normalizeExtensionName(string $name): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9.]+/i', '', $name));
    }

    private function authorityInfoAccessFromDer(string $certificatePem): ?string
    {
        try {
            $certificateDer = (new CertificateFormatNormalizer())
                ->normalizeToDer($certificatePem);

            $reader = new DerReader();
            $certificate = $reader->children($certificateDer);

            if ($certificate === [] || $certificate[0]['tag'] !== 0x30) {
                return null;
            }

            $tbsCertificate = $reader->childrenFromContent($certificate[0]['content']);

            foreach ($tbsCertificate as $field) {
                if ($field['tag'] !== 0xA3) {
                    continue;
                }

                $extensionSequenceOffset = 0;
                $extensionsSequence = $reader->readTlv($field['content'], $extensionSequenceOffset);

                if ($extensionsSequence['tag'] !== 0x30) {
                    return null;
                }

                return $this->authorityInfoAccessFromExtensions(
                    $reader->childrenFromContent($extensionsSequence['content'])
                );
            }
        } catch (RuntimeException) {
            return null;
        }

        return null;
    }

    /**
     * @param array<int, array{tag:int,content:string,encoded:string}> $extensions
     */
    private function authorityInfoAccessFromExtensions(array $extensions): ?string
    {
        $reader = new DerReader();

        foreach ($extensions as $extension) {
            if ($extension['tag'] !== 0x30) {
                continue;
            }

            $fields = $reader->childrenFromContent($extension['content']);

            if ($fields === [] || $fields[0]['tag'] !== 0x06) {
                continue;
            }

            if (strtoupper(bin2hex($fields[0]['content'])) !== '2B06010505070101') {
                continue;
            }

            $value = $this->extensionOctetString($fields);

            if ($value === null) {
                return null;
            }

            return $this->formatAuthorityInfoAccess(
                $this->accessDescriptions($value['content'])
            );
        }

        return null;
    }

    /**
     * @param array<int, array{tag:int,content:string}> $fields
     * @return array{tag:int,content:string}|null
     */
    private function extensionOctetString(array $fields): ?array
    {
        foreach (array_slice($fields, 1) as $field) {
            if ($field['tag'] === 0x04) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array<int, array{method:string, uri:string}>
     */
    private function accessDescriptions(string $authorityInfoAccessDer): array
    {
        $reader = new DerReader();
        $aia = $reader->children($authorityInfoAccessDer);
        $descriptions = [];

        foreach ($aia as $description) {
            if ($description['tag'] !== 0x30) {
                continue;
            }

            $fields = $reader->childrenFromContent($description['content']);

            if (count($fields) < 2 || $fields[0]['tag'] !== 0x06) {
                continue;
            }

            if ($fields[1]['tag'] !== 0x86) {
                continue;
            }

            $descriptions[] = [
                'method' => strtoupper(bin2hex($fields[0]['content'])),
                'uri' => $fields[1]['content'],
            ];
        }

        return $descriptions;
    }

    /**
     * @param array<int, array{method:string, uri:string}> $descriptions
     */
    private function formatAuthorityInfoAccess(array $descriptions): ?string
    {
        $lines = [];

        foreach ($descriptions as $description) {
            $label = match ($description['method']) {
                '2B06010505073001' => 'OCSP',
                '2B06010505073002' => 'CA Issuers',
                default => null,
            };

            if ($label === null) {
                continue;
            }

            $lines[] = "{$label} - URI:{$description['uri']}";
        }

        if ($lines === []) {
            return null;
        }

        return implode("\n", $lines);
    }

    private function authorityInfoAccessByOidScan(string $certificatePem): ?string
    {
        try {
            $certificateDer = (new CertificateFormatNormalizer())
                ->normalizeToDer($certificatePem);
        } catch (RuntimeException) {
            return null;
        }

        $descriptions = [];

        foreach ([
            '2B06010505073001' => 'OCSP',
            '2B06010505073002' => 'CA Issuers',
        ] as $methodOidHex => $label) {
            $methodOid = hex2bin($methodOidHex);

            if ($methodOid === false) {
                continue;
            }

            $offset = 0;

            while (($position = strpos($certificateDer, $methodOid, $offset)) !== false) {
                $uri = $this->nextUriAfterPosition(
                    der: $certificateDer,
                    position: $position + strlen($methodOid)
                );

                if ($uri !== null) {
                    $descriptions[] = "{$label} - URI:{$uri}";
                }

                $offset = $position + strlen($methodOid);
            }
        }

        if ($descriptions === []) {
            return null;
        }

        return implode("\n", array_values(array_unique($descriptions)));
    }

    private function crlDistributionPointsByOidScan(string $certificatePem): ?string
    {
        try {
            $certificateDer = (new CertificateFormatNormalizer())
                ->normalizeToDer($certificatePem);
        } catch (RuntimeException) {
            return null;
        }

        $crlDistributionPointsOid = hex2bin('551D1F');

        if ($crlDistributionPointsOid === false) {
            return null;
        }

        $descriptions = [];
        $offset = 0;

        while (($position = strpos($certificateDer, $crlDistributionPointsOid, $offset)) !== false) {
            $uri = $this->nextUriAfterPosition(
                der: $certificateDer,
                position: $position + strlen($crlDistributionPointsOid)
            );

            if ($uri !== null) {
                $descriptions[] = "URI:{$uri}";
            }

            $offset = $position + strlen($crlDistributionPointsOid);
        }

        if ($descriptions === []) {
            return null;
        }

        return implode("\n", array_values(array_unique($descriptions)));
    }

    private function nextUriAfterPosition(
        string $der,
        int $position
    ): ?string {
        $reader = new DerReader();
        $limit = min(strlen($der), $position + 1024);

        for ($offset = $position; $offset < $limit; $offset++) {
            if (! isset($der[$offset]) || ord($der[$offset]) !== 0x86) {
                continue;
            }

            try {
                $candidateOffset = $offset;
                $uri = $reader->readTlv($der, $candidateOffset);
            } catch (RuntimeException) {
                continue;
            }

            if ($uri['tag'] !== 0x86 || $uri['content'] === '') {
                continue;
            }

            if (preg_match('/^https?:\/\//i', $uri['content']) === 1) {
                return $uri['content'];
            }
        }

        return null;
    }
}
