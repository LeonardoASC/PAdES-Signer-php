<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\IcpBrasil;

use DateTimeImmutable;
use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMXPath;
use NihilLabs\Pades\Crypto\Asn1\DerReader;
use RuntimeException;

final readonly class LpaParser
{
    private const TAG_SEQUENCE = 0x30;
    private const TAG_OID = 0x06;
    private const TAG_OCTET_STRING = 0x04;
    private const TAG_IA5_STRING = 0x16;
    private const TAG_GENERALIZED_TIME = 0x18;

    /**
     * @return array<int, LpaPolicyEntry>
     */
    public function parse(string $lpaBytes): array
    {
        if ($lpaBytes === '') {
            throw new RuntimeException('LPA bytes must not be empty.');
        }

        $trimmed = ltrim($lpaBytes);

        if (str_starts_with($trimmed, '<')) {
            return $this->parseXml($lpaBytes);
        }

        try {
            return $this->parseDer($lpaBytes);
        } catch (RuntimeException $exception) {
            return $this->parseEmbeddedLpa($lpaBytes, $exception);
        }
    }

    /**
     * @param array<int, LpaPolicyEntry> $policies
     */
    public function selectLatestCurrentPdfPolicy(
        array $policies,
        string $signatureType,
        ?DateTimeImmutable $at = null
    ): ?LpaPolicyEntry {
        $at ??= new DateTimeImmutable('now');

        $candidates = array_values(array_filter(
            $policies,
            static fn (LpaPolicyEntry $policy): bool => $policy->isPdfPolicy()
                && $policy->isSignatureType($signatureType)
                && $policy->isCurrent($at)
        ));

        usort(
            $candidates,
            static function (LpaPolicyEntry $left, LpaPolicyEntry $right): int {
                $leftDate = $left->validFrom?->getTimestamp() ?? 0;
                $rightDate = $right->validFrom?->getTimestamp() ?? 0;

                if ($leftDate !== $rightDate) {
                    return $rightDate <=> $leftDate;
                }

                return version_compare(
                    $right->version ?? '0',
                    $left->version ?? '0'
                );
            }
        );

        return $candidates[0] ?? null;
    }

    /**
     * @return array<int, LpaPolicyEntry>
     */
    private function parseDer(string $der): array
    {
        $reader = new DerReader();
        $offset = 0;
        $root = $reader->readTlv($der, $offset);

        if ($offset !== strlen($der) || $root['tag'] !== self::TAG_SEQUENCE) {
            throw new RuntimeException('Invalid LPA DER root.');
        }

        $rootChildren = $reader->childrenFromContent($root['content']);
        $list = $rootChildren[0] ?? null;

        if ($list === null || $list['tag'] !== self::TAG_SEQUENCE) {
            throw new RuntimeException('Invalid LPA policy list.');
        }

        $nextUpdate = null;
        $lastRootChild = $rootChildren[count($rootChildren) - 1] ?? null;

        if (is_array($lastRootChild) && $lastRootChild['tag'] === self::TAG_GENERALIZED_TIME) {
            $nextUpdate = $this->parseGeneralizedTime($lastRootChild['content']);
        }

        $policies = [];

        foreach ($reader->childrenFromContent($list['content']) as $policyTlv) {
            if ($policyTlv['tag'] !== self::TAG_SEQUENCE) {
                continue;
            }

            $policy = $this->parseDerPolicyEntry($policyTlv, $nextUpdate);

            if ($policy !== null) {
                $policies[] = $policy;
            }
        }

        if ($policies === []) {
            throw new RuntimeException('No ICP-Brasil policy entries found in LPA.');
        }

        return $policies;
    }

    /**
     * @return array<int, LpaPolicyEntry>
     */
    private function parseEmbeddedLpa(
        string $containerDer,
        RuntimeException $originalException
    ): array {
        $candidates = [];
        $this->collectOctetStringContents($containerDer, $candidates);

        foreach ($candidates as $candidate) {
            try {
                if (str_starts_with(ltrim($candidate), '<')) {
                    return $this->parseXml($candidate);
                }

                if (isset($candidate[0]) && ord($candidate[0]) === self::TAG_SEQUENCE) {
                    return $this->parseDer($candidate);
                }
            } catch (RuntimeException) {
            }
        }

        throw $originalException;
    }

    /**
     * @param array<int, string> $contents
     */
    private function collectOctetStringContents(
        string $encoded,
        array &$contents,
        int $depth = 0
    ): void {
        if ($depth > 12 || $encoded === '') {
            return;
        }

        $reader = new DerReader();
        $offset = 0;
        $length = strlen($encoded);

        while ($offset < $length) {
            try {
                $tlv = $reader->readTlv($encoded, $offset);
            } catch (RuntimeException) {
                return;
            }

            if ($tlv['tag'] === self::TAG_OCTET_STRING) {
                $contents[] = $tlv['content'];
                $this->collectOctetStringContents($tlv['content'], $contents, $depth + 1);

                continue;
            }

            if (($tlv['tag'] & 0x20) === 0x20) {
                $this->collectOctetStringContents($tlv['content'], $contents, $depth + 1);
            }
        }
    }

    /**
     * @param array{
     *     tag:int,
     *     content:string
     * } $policyTlv
     */
    private function parseDerPolicyEntry(
        array $policyTlv,
        ?DateTimeImmutable $nextUpdate
    ): ?LpaPolicyEntry {
        $reader = new DerReader();
        $children = $reader->childrenFromContent($policyTlv['content']);

        if (count($children) < 4 || $children[0]['tag'] !== self::TAG_SEQUENCE) {
            return null;
        }

        $period = $reader->childrenFromContent($children[0]['content']);

        if (
            count($period) < 2
            || $period[0]['tag'] !== self::TAG_GENERALIZED_TIME
            || $period[1]['tag'] !== self::TAG_GENERALIZED_TIME
        ) {
            return null;
        }

        $index = 1;
        $revokedAt = null;

        if (($children[$index]['tag'] ?? null) === self::TAG_GENERALIZED_TIME) {
            $revokedAt = $this->parseGeneralizedTime($children[$index]['content']);
            $index++;
        }

        $oidTlv = $children[$index++] ?? null;
        $uriTlv = $children[$index++] ?? null;
        $hashTlv = $children[$index] ?? null;

        if (
            ! is_array($oidTlv)
            || ! is_array($uriTlv)
            || ! is_array($hashTlv)
            || $oidTlv['tag'] !== self::TAG_OID
            || $uriTlv['tag'] !== self::TAG_IA5_STRING
            || $hashTlv['tag'] !== self::TAG_SEQUENCE
        ) {
            return null;
        }

        [$hashAlgorithmOid, $policyHash] = $this->parseHash($hashTlv['content']);
        $policyUri = $uriTlv['content'];
        $policyOid = $this->decodeOid($oidTlv['content']);

        return new LpaPolicyEntry(
            policyOid: $policyOid,
            policyUri: $policyUri,
            policyHash: $policyHash,
            hashAlgorithmOid: $hashAlgorithmOid,
            signatureType: $this->signatureTypeFromUriOrOid($policyUri, $policyOid),
            format: $this->formatFromUri($policyUri),
            version: $this->versionFromUri($policyUri),
            validFrom: $this->parseGeneralizedTime($period[0]['content']),
            validUntil: $this->parseGeneralizedTime($period[1]['content']),
            revokedAt: $revokedAt,
            artifacts: [
                'lpaNextUpdate' => $nextUpdate?->format(DATE_ATOM),
            ]
        );
    }

    /**
     * @return array{0:string, 1:string}
     */
    private function parseHash(string $hashContent): array
    {
        $reader = new DerReader();
        $children = $reader->childrenFromContent($hashContent);

        if (
            count($children) < 2
            || $children[0]['tag'] !== self::TAG_SEQUENCE
            || $children[1]['tag'] !== self::TAG_OCTET_STRING
        ) {
            throw new RuntimeException('Invalid LPA policy hash structure.');
        }

        $algorithm = $reader->childrenFromContent($children[0]['content']);

        if (($algorithm[0]['tag'] ?? null) !== self::TAG_OID) {
            throw new RuntimeException('Invalid LPA policy hash algorithm.');
        }

        return [
            $this->decodeOid($algorithm[0]['content']),
            $children[1]['content'],
        ];
    }

    /**
     * @return array<int, LpaPolicyEntry>
     */
    private function parseXml(string $xml): array
    {
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadXML($xml);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (! $loaded) {
            throw new RuntimeException('Invalid LPA XML.');
        }

        $xpath = new DOMXPath($document);
        $nodes = $xpath->query('//*[contains(., "PA_")]');

        if ($nodes === false) {
            throw new RuntimeException('Cannot query LPA XML.');
        }

        $policies = [];
        $seenUris = [];

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $text = $node->textContent;

            preg_match_all(
                '/https?:\/\/[^\s<>"\']+PA_[^\s<>"\']+\.(?:der|xml|pdf|asn1)/i',
                $text,
                $uriMatches
            );

            foreach ($uriMatches[0] as $uri) {
                if (isset($seenUris[$uri])) {
                    continue;
                }

                $context = $this->xmlContextForUri($node, $uri);
                $policy = $this->parseXmlPolicyContext($uri, $context);

                if ($policy !== null) {
                    $seenUris[$uri] = true;
                    $policies[] = $policy;
                }
            }
        }

        if ($policies === []) {
            throw new RuntimeException('No ICP-Brasil policy entries found in LPA XML.');
        }

        return $policies;
    }

    private function xmlContextForUri(DOMElement $node, string $uri): string
    {
        $current = $node;
        $best = $node->textContent;

        while ($current->parentNode instanceof DOMElement) {
            $candidate = $current->parentNode->textContent;

            if (substr_count($candidate, $uri) !== 1) {
                break;
            }

            if (substr_count($candidate, 'PA_') > 1) {
                break;
            }

            $best = $candidate;
            $current = $current->parentNode;
        }

        return $best;
    }

    private function parseXmlPolicyContext(
        string $uri,
        string $context
    ): ?LpaPolicyEntry {
        if (preg_match('/\b2\.16\.76\.1\.7\.1\.\d+(?:\.\d+)+\b/', $context, $oidMatch) !== 1) {
            return null;
        }

        if (preg_match('/\b[0-9a-fA-F]{64}\b/', $context, $hashMatch) !== 1) {
            return null;
        }

        preg_match_all(
            '/\b\d{14}Z\b|\b\d{2}\/\d{2}\/\d{4}\b/',
            $context,
            $dateMatches
        );

        return new LpaPolicyEntry(
            policyOid: $oidMatch[0],
            policyUri: $uri,
            policyHash: hex2bin($hashMatch[0]) ?: '',
            hashAlgorithmOid: '2.16.840.1.101.3.4.2.1',
            signatureType: $this->signatureTypeFromUriOrOid($uri, $oidMatch[0]),
            format: $this->formatFromUri($uri),
            version: $this->versionFromUri($uri),
            validFrom: $this->parseTextDate($dateMatches[0][0] ?? null),
            validUntil: $this->parseTextDate($dateMatches[0][1] ?? null),
            revokedAt: $this->parseTextDate($dateMatches[0][2] ?? null),
        );
    }

    private function parseTextDate(?string $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/^\d{14}Z$/', $value) === 1) {
            return $this->parseGeneralizedTime($value);
        }

        $date = DateTimeImmutable::createFromFormat(
            '!d/m/Y H:i:s',
            $value . ' 00:00:00',
            new DateTimeZone('UTC')
        );

        return $date instanceof DateTimeImmutable ? $date : null;
    }

    private function parseGeneralizedTime(string $value): DateTimeImmutable
    {
        $date = DateTimeImmutable::createFromFormat(
            '!YmdHis\Z',
            $value,
            new DateTimeZone('UTC')
        );

        if (! $date instanceof DateTimeImmutable) {
            throw new RuntimeException('Invalid LPA generalized time.');
        }

        return $date;
    }

    private function signatureTypeFromUriOrOid(
        string $uri,
        string $oid
    ): string {
        if (preg_match('/AD[_-](RB|RT|RC|RA|RV)/i', $uri, $match) === 1) {
            return 'AD-' . strtoupper($match[1]);
        }

        return match (true) {
            str_starts_with($oid, '2.16.76.1.7.1.11.1') => 'AD-RB',
            str_starts_with($oid, '2.16.76.1.7.1.12.1') => 'AD-RT',
            str_starts_with($oid, '2.16.76.1.7.1.13.1') => 'AD-RC',
            str_starts_with($oid, '2.16.76.1.7.1.14.1') => 'AD-RA',
            default => 'UNKNOWN',
        };
    }

    private function formatFromUri(string $uri): string
    {
        return match (true) {
            stripos($uri, 'PAdES') !== false => 'PAdES',
            stripos($uri, 'CAdES') !== false => 'CAdES',
            stripos($uri, 'XAdES') !== false => 'XAdES',
            default => 'UNKNOWN',
        };
    }

    private function versionFromUri(string $uri): ?string
    {
        if (preg_match('/_v(\d+(?:_\d+)*)\.(?:der|xml|pdf|asn1)$/i', $uri, $match) !== 1) {
            return null;
        }

        return str_replace('_', '.', $match[1]);
    }

    private function decodeOid(string $content): string
    {
        if ($content === '') {
            throw new RuntimeException('Invalid empty OID.');
        }

        $first = ord($content[0]);

        if ($first >= 80) {
            $arcs = [2, $first - 80];
        } else {
            $arcs = [intdiv($first, 40), $first % 40];
        }

        $value = 0;
        $length = strlen($content);

        for ($index = 1; $index < $length; $index++) {
            $byte = ord($content[$index]);
            $value = ($value << 7) | ($byte & 0x7F);

            if (($byte & 0x80) === 0) {
                $arcs[] = $value;
                $value = 0;
            }
        }

        return implode('.', $arcs);
    }
}
