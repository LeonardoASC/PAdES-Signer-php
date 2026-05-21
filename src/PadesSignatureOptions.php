<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Signing\SignerProviderInterface;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;
use NihilLabs\Pades\Validation\TrustValidatorInterface;

final readonly class PadesSignatureOptions
{
    public const string SIGNATURE_TYPE_APPROVAL = 'approval';
    public const string SIGNATURE_TYPE_CERTIFICATION = 'certification';

    public const string FIELD_LOCK_ALL = 'All';
    public const string FIELD_LOCK_INCLUDE = 'Include';
    public const string FIELD_LOCK_EXCLUDE = 'Exclude';

    /**
     * @param array{0:int,1:int,2:int,3:int} $signatureRect
     * @param array<string> $lockedFieldNames
     */
    public function __construct(
        public ?TimestampProviderInterface $timestampProvider = null,
        public ?TrustValidatorInterface $trustValidator = null,
        public ?SignerProviderInterface $signerProvider = null,
        public bool $visibleSignature = false,
        public array $signatureRect = [48, 48, 547, 96],
        public int $signatureFlags = 132,
        public string $signatureName = 'PAdES Core',
        public string $signatureReason = 'Document signed digitally',
        public ?string $signatureLocation = null,
        public ?string $signatureContactInfo = null,
        public ?string $signatureFieldName = null,
        public string $signatureType = self::SIGNATURE_TYPE_APPROVAL,
        public int $certificationPermission = 2,
        public array $lockedFieldNames = [],
        public string $fieldLockAction = self::FIELD_LOCK_INCLUDE,
        public string $hashAlgorithm = SignatureAlgorithmPolicy::HASH_SHA256,
        public string $signatureAlgorithm = SignatureAlgorithmPolicy::SIGNATURE_RSA,
        public string $minimumHashAlgorithm = SignatureAlgorithmPolicy::HASH_SHA256
    ) {}

    public function algorithmPolicy(): SignatureAlgorithmPolicy
    {
        return new SignatureAlgorithmPolicy(
            hashAlgorithm: $this->hashAlgorithm,
            signatureAlgorithm: $this->signatureAlgorithm,
            minimumHashAlgorithm: $this->minimumHashAlgorithm
        );
    }
}
