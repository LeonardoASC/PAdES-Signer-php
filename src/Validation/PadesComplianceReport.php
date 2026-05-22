<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

final readonly class PadesComplianceReport
{
    /**
     * @param array<string, bool> $inspection
     */
    public function fromInspection(array $inspection): array
    {
        return [
            'level' => $this->level($inspection),

            'checks' => [
                'CMS SignedData' => $inspection['is_cms_signed_data'],
                'contentType' => $inspection['has_content_type'] ?? false,
                'messageDigest' => $inspection['has_message_digest'] ?? false,
                'signingTime' => $inspection['has_signing_time'] ?? false,
                'SigningCertificateV2' => $inspection['has_signing_certificate_v2'],
                'signatureTimeStampToken' => $inspection['has_signature_timestamp_token'] ?? false,
            ],

            'ready' => ($inspection['is_pades_b_lta_ready'] ?? false)
                || ($inspection['is_pades_b_lt_ready'] ?? false)
                || ($inspection['is_pades_b_t_ready'] ?? false)
                || $inspection['is_pades_b_b_ready'],
        ];
    }

    /**
     * @param array<string, bool> $inspection
     */
    private function level(array $inspection): string
    {
        if ($inspection['is_pades_b_lta_ready'] ?? false) {
            return 'PAdES-B-LTA';
        }

        if ($inspection['is_pades_b_lt_ready'] ?? false) {
            return 'PAdES-B-LT';
        }

        if ($inspection['is_pades_b_t_ready'] ?? false) {
            return 'PAdES-B-T';
        }

        return $inspection['is_pades_b_b_ready']
            ? 'PAdES-B-B'
            : 'Not PAdES-B-B';
    }
}
