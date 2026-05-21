<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Signing\SignerProviderInterface;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;
use NihilLabs\Pades\Validation\TrustValidatorInterface;

final readonly class PadesSignatureOptions
{
    /**
     * @param array{0:int,1:int,2:int,3:int} $signatureRect
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
        public ?string $signatureContactInfo = null
    ) {}
}
