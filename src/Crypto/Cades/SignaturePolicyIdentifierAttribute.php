<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignaturePolicyIdentifierAttribute
{
    private const ID_AA_ETS_SIG_POLICY_ID_OID_HEX = '2A864886F70D010910020F';

    public function build(IcpBrasilSignaturePolicy $policy): string
    {
        return Der::sequence(
            Der::oid(self::ID_AA_ETS_SIG_POLICY_ID_OID_HEX)
                . Der::set(
                    $this->signaturePolicyIdentifier($policy)
                )
        );
    }

    private function signaturePolicyIdentifier(
        IcpBrasilSignaturePolicy $policy
    ): string {
        return Der::sequence(
            $this->oid($policy->policyOid)
                . $this->otherHashAlgAndValue($policy->policyHash)
                . $this->qualifiers($policy)
        );
    }

    private function policyHashBytes(string $policyHash): string
    {
        if (preg_match('/^[0-9A-Fa-f]{64}$/', $policyHash) === 1) {
            $bytes = hex2bin($policyHash);

            if ($bytes !== false) {
                return $bytes;
            }
        }

        return $policyHash;
    }

    private function otherHashAlgAndValue(string $policyHash): string
    {
        return Der::sequence(
            Der::sha256AlgorithmIdentifier()
                . Der::octetString($this->policyHashBytes($policyHash))
        );
    }

    private function qualifiers(IcpBrasilSignaturePolicy $policy): string
    {
        if ($policy->policyUri === null || $policy->policyUri === '') {
            return '';
        }

        return Der::sequence(
            (new SignaturePolicyQualifier())->spuri($policy->policyUri)
        );
    }

    private function oid(string $oid): string
    {
        if (preg_match('/^\d+(?:\.\d+)+$/', $oid) === 1) {
            return Der::oid($this->dottedOidToHex($oid));
        }

        if (preg_match('/^[0-9A-Fa-f]+$/', $oid) === 1) {
            return Der::oid($oid);
        }

        throw new InvalidArgumentException('Invalid policy OID.');
    }

    private function dottedOidToHex(string $oid): string
    {
        $parts = array_map('intval', explode('.', $oid));

        if (count($parts) < 2 || $parts[0] > 2 || $parts[1] > 39) {
            throw new InvalidArgumentException('Invalid dotted policy OID.');
        }

        $encoded = chr(($parts[0] * 40) + $parts[1]);

        foreach (array_slice($parts, 2) as $part) {
            $encoded .= $this->base128($part);
        }

        return strtoupper(bin2hex($encoded));
    }

    private function base128(int $value): string
    {
        if ($value < 0) {
            throw new InvalidArgumentException('Invalid OID arc.');
        }

        $bytes = [chr($value & 0x7F)];
        $value >>= 7;

        while ($value > 0) {
            array_unshift($bytes, chr(($value & 0x7F) | 0x80));
            $value >>= 7;
        }

        return implode('', $bytes);
    }
}
