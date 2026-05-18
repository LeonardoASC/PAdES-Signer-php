<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

final readonly class PadesBaselineInspector
{
    public function inspect(string $binaryCms): array
    {
        $profile = new CmsBaselineProfile();

        $hasSignedData = $profile
            ->hasSignedDataOid($binaryCms);

        $hasSigningCertificateV2 = $profile
            ->hasSigningCertificateV2Oid($binaryCms);

        return [
            'is_cms_signed_data' => $hasSignedData,

            'has_signing_certificate_v2' => $hasSigningCertificateV2,

            'is_pades_b_b_ready' => (
                $hasSignedData
                && $hasSigningCertificateV2
            ),
        ];
    }
}