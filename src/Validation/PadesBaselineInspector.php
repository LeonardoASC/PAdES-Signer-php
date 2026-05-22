<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Internal\Crypto\CmsBaselineProfile;

final readonly class PadesBaselineInspector
{
    public function inspect(string $binaryCms): array
    {
        $profile = new CmsBaselineProfile();

        $hasSignedData = $profile
            ->hasSignedDataOid($binaryCms);

        $hasSigningCertificateV2 = $profile
            ->hasSigningCertificateV2Oid($binaryCms);
        $hasContentType = $profile
            ->hasContentTypeAttributeOid($binaryCms);
        $hasMessageDigest = $profile
            ->hasMessageDigestAttributeOid($binaryCms);
        $hasSigningTime = $profile
            ->hasSigningTimeAttributeOid($binaryCms);
        $hasSignatureTimestampToken = $profile
            ->hasSignatureTimestampTokenAttributeOid($binaryCms);

        return [
            'is_cms_signed_data' => $hasSignedData,

            'has_content_type' => $hasContentType,

            'has_message_digest' => $hasMessageDigest,

            'has_signing_time' => $hasSigningTime,

            'has_signing_certificate_v2' => $hasSigningCertificateV2,

            'has_signature_timestamp_token' => $hasSignatureTimestampToken,

            'is_pades_b_b_ready' => (
                $hasSignedData
                && $hasContentType
                && $hasMessageDigest
                && $hasSigningCertificateV2
            ),

            'is_pades_b_t_ready' => (
                $hasSignedData
                && $hasContentType
                && $hasMessageDigest
                && $hasSigningCertificateV2
                && $hasSignatureTimestampToken
            ),
        ];
    }
}
