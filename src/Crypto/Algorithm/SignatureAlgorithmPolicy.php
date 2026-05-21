<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Algorithm;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Asn1\Der;

final readonly class SignatureAlgorithmPolicy
{
    public const string HASH_SHA256 = 'sha256';
    public const string HASH_SHA384 = 'sha384';
    public const string HASH_SHA512 = 'sha512';

    public const string SIGNATURE_RSA = 'rsa';
    public const string SIGNATURE_RSA_PSS = 'rsa-pss';
    public const string SIGNATURE_ECDSA = 'ecdsa';

    private const array HASH_STRENGTH = [
        self::HASH_SHA256 => 256,
        self::HASH_SHA384 => 384,
        self::HASH_SHA512 => 512,
    ];

    public function __construct(
        public string $hashAlgorithm = self::HASH_SHA256,
        public string $signatureAlgorithm = self::SIGNATURE_RSA,
        public string $minimumHashAlgorithm = self::HASH_SHA256
    ) {
        $this->validateHashAlgorithm($hashAlgorithm);
        $this->validateHashAlgorithm($minimumHashAlgorithm);

        if (! in_array($signatureAlgorithm, [
            self::SIGNATURE_RSA,
            self::SIGNATURE_RSA_PSS,
            self::SIGNATURE_ECDSA,
        ], true)) {
            throw new InvalidArgumentException('Unsupported signature algorithm.');
        }

        if (self::HASH_STRENGTH[$hashAlgorithm] < self::HASH_STRENGTH[$minimumHashAlgorithm]) {
            throw new InvalidArgumentException('Hash algorithm is weaker than the configured minimum.');
        }
    }

    public static function default(): self
    {
        return new self();
    }

    public function digestAlgorithmIdentifier(): string
    {
        return Der::algorithmIdentifier($this->digestOidHex(), withNull: true);
    }

    public function signatureAlgorithmIdentifier(): string
    {
        return match ($this->signatureAlgorithm) {
            self::SIGNATURE_RSA => Der::algorithmIdentifier($this->rsaPkcs1OidHex(), withNull: true),
            self::SIGNATURE_RSA_PSS => Der::sequence(
                Der::oid('2a864886f70d01010a')
                    . $this->rsaPssParameters()
            ),
            self::SIGNATURE_ECDSA => Der::algorithmIdentifier($this->ecdsaOidHex(), withNull: false),
        };
    }

    public function openSslDigestAlgorithm(): int
    {
        return match ($this->hashAlgorithm) {
            self::HASH_SHA256 => OPENSSL_ALGO_SHA256,
            self::HASH_SHA384 => OPENSSL_ALGO_SHA384,
            self::HASH_SHA512 => OPENSSL_ALGO_SHA512,
        };
    }

    public function hash(string $data): string
    {
        return hash($this->hashAlgorithm, $data, binary: true);
    }

    public function isLocalOpenSslSupported(): bool
    {
        return $this->signatureAlgorithm !== self::SIGNATURE_RSA_PSS;
    }

    private function validateHashAlgorithm(string $algorithm): void
    {
        if (! isset(self::HASH_STRENGTH[$algorithm])) {
            throw new InvalidArgumentException('Unsupported hash algorithm.');
        }
    }

    private function digestOidHex(): string
    {
        return match ($this->hashAlgorithm) {
            self::HASH_SHA256 => '608648016503040201',
            self::HASH_SHA384 => '608648016503040202',
            self::HASH_SHA512 => '608648016503040203',
        };
    }

    private function rsaPkcs1OidHex(): string
    {
        return match ($this->hashAlgorithm) {
            self::HASH_SHA256 => '2a864886f70d01010b',
            self::HASH_SHA384 => '2a864886f70d01010c',
            self::HASH_SHA512 => '2a864886f70d01010d',
        };
    }

    private function ecdsaOidHex(): string
    {
        return match ($this->hashAlgorithm) {
            self::HASH_SHA256 => '2a8648ce3d040302',
            self::HASH_SHA384 => '2a8648ce3d040303',
            self::HASH_SHA512 => '2a8648ce3d040304',
        };
    }

    private function rsaPssParameters(): string
    {
        $hashAlgorithm = $this->digestAlgorithmIdentifier();
        $mgf1 = Der::algorithmIdentifier(
            '2a864886f70d010108',
            parameters: $hashAlgorithm
        );

        return Der::sequence(
            Der::contextSpecificConstructed(0, $hashAlgorithm)
                . Der::contextSpecificConstructed(1, $mgf1)
                . Der::contextSpecificConstructed(2, Der::integer($this->saltLength()))
        );
    }

    private function saltLength(): int
    {
        return intdiv(self::HASH_STRENGTH[$this->hashAlgorithm], 8);
    }
}
