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
            'level' => $inspection['is_pades_b_b_ready']
                ? 'PAdES-B-B'
                : 'Not PAdES-B-B',

            'checks' => [
                'CMS SignedData' => $inspection['is_cms_signed_data'],
                'SigningCertificateV2' => $inspection['has_signing_certificate_v2'],
            ],

            'ready' => $inspection['is_pades_b_b_ready'],
        ];
    }
}
