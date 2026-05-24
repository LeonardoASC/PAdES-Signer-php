<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Internal\Crypto\CmsSignedDataParser;
use RuntimeException;

final readonly class PadesBaselineInspector
{
    public function inspect(string $binaryCms): array
    {
        try {
            $cms = (new CmsSignedDataParser())->parse($binaryCms);
            $hasSignedData = $cms->contentTypeOid === '1.2.840.113549.1.7.2';
            $hasContentType = $cms->signedAttribute('1.2.840.113549.1.9.3') !== null;
            $hasMessageDigest = $cms->signedAttribute('1.2.840.113549.1.9.4') !== null;
            $hasSigningTime = $cms->signedAttribute('1.2.840.113549.1.9.5') !== null;
            $hasSigningCertificateV2 = $cms->signedAttribute('1.2.840.113549.1.9.16.2.47') !== null;
            $hasSignatureTimestampToken = $cms->hasUnsignedAttributes
                && str_contains($binaryCms, hex2bin('060b2a864886f70d010910020e'));
        } catch (RuntimeException) {
            $hasSignedData = false;
            $hasContentType = false;
            $hasMessageDigest = false;
            $hasSigningTime = false;
            $hasSigningCertificateV2 = false;
            $hasSignatureTimestampToken = false;
        }

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
