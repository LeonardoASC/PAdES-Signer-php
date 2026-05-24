<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use NihilLabs\Pades\Crypto\Crl\CrlValidator;
use NihilLabs\Pades\Crypto\Ocsp\OcspResponseValidator;

final readonly class RevocationMaterialValidator
{
    /**
     * @param array<string> $chainPem
     */
    public function validate(array $chainPem, RevocationMaterial $material, ?\DateTimeInterface $validationTime = null): RevocationValidationResult
    {
        if (count($chainPem) < 2) {
            return new RevocationValidationResult(false, ['chain_available' => false], ['Cadeia insuficiente para validar revogacao.']);
        }

        $certificate = $chainPem[0];
        $issuer = $chainPem[1];
        $ocspValid = false;
        $crlValid = false;

        foreach ($material->ocspResponsesDer as $ocsp) {
            $result = (new OcspResponseValidator())->validate($ocsp, $certificate, $issuer, validationTime: $validationTime);

            if ($result->isGood()) {
                $ocspValid = true;
                break;
            }
        }

        foreach ($material->crlsDer as $crl) {
            if ((new CrlValidator())->validate($crl, $issuer, $certificate, $validationTime)) {
                $crlValid = true;
                break;
            }
        }

        $checks = [
            'chain_available' => true,
            'ocsp_valid' => $ocspValid,
            'crl_valid' => $crlValid,
            'has_usable_revocation_data' => $ocspValid || $crlValid,
        ];
        $messages = [];

        if (! $checks['has_usable_revocation_data']) {
            $messages[] = 'Nenhum OCSP/CRL valido para o certificado da cadeia.';
        }

        return new RevocationValidationResult($messages === [], $checks, $messages);
    }
}
