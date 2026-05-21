<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

final readonly class CmsBaselineProfile
{
    public function hasSignedDataOid(string $binaryCms): bool
    {
        return str_contains(
            bin2hex($binaryCms),
            '06092a864886f70d010702'
        );
    }

    public function hasSigningCertificateV2Oid(string $binaryCms): bool
    {
        return str_contains(
            bin2hex($binaryCms),
            '060b2a864886f70d010910022f'
        );
    }

    public function inspect(string $binaryCms): array
    {
        return [
            'has_signed_data_oid' => $this->hasSignedDataOid($binaryCms),
            'has_signing_certificate_v2_oid' => $this->hasSigningCertificateV2Oid($binaryCms),
        ];
    }
}
