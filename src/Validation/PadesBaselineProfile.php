<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

final readonly class PadesBaselineProfile
{
    public const string B_B = 'PAdES-B-B';
    public const string B_T = 'PAdES-B-T';
    public const string B_LT = 'PAdES-B-LT';
    public const string B_LTA = 'PAdES-B-LTA';

    /**
     * @return array<string>
     */
    public function all(): array
    {
        return [
            self::B_B,
            self::B_T,
            self::B_LT,
            self::B_LTA,
        ];
    }

    /**
     * @return array<string>
     */
    public function requirements(string $profile): array
    {
        return match ($profile) {
            self::B_B => [
                'pdf_signature_dictionary',
                'etsi_cades_detached_subfilter',
                'valid_byte_range',
                'cms_signed_data',
                'content_type_attribute',
                'message_digest_attribute',
                'signing_certificate_v2_attribute',
            ],
            self::B_T => [
                ...$this->requirements(self::B_B),
                'signature_timestamp_token',
            ],
            self::B_LT => [
                ...$this->requirements(self::B_T),
                'dss_dictionary',
                'vri_dictionary',
                'vri_hash_matches_signature',
                'dss_certificate_chain',
                'dss_ocsp_responses',
                'dss_crls',
                'offline_validation_ready',
            ],
            self::B_LTA => [
                ...$this->requirements(self::B_LT),
                'document_timestamp',
                'archival_timestamp_after_dss',
                'archival_timestamp_token_valid',
            ],
            default => [],
        };
    }
}
