<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

use NihilLabs\Pades\Crypto\X509\CertificateFormatNormalizer;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;

final readonly class CmsSignerCertificateExtractor
{
    public function extract(string $cmsDer): CmsSignerCertificateInfo
    {
        $signedData = (new CmsSignedDataParser())->parse($cmsDer);
        $signerIdentifier = $signedData->signerIdentifierDer;

        foreach ($signedData->certificatesDer as $certificateDer) {
            $certificatePem = (new CertificateFormatNormalizer())->toPem($certificateDer);
            $certificateIdentifier = (new X509NameDerExtractor())->extractIssuerNameDer($certificatePem)
                . (new X509NameDerExtractor())->extractSerialNumberDer($certificatePem);

            if (! hash_equals($signerIdentifier, $certificateIdentifier)) {
                continue;
            }

            return new CmsSignerCertificateInfo(
                signerIdentifierDer: $signerIdentifier,
                certificateDer: $certificateDer,
                certificatePem: $certificatePem,
                matchesSignerInfo: true
            );
        }

        return new CmsSignerCertificateInfo(
            signerIdentifierDer: $signerIdentifier,
            certificateDer: null,
            certificatePem: null,
            matchesSignerInfo: false
        );
    }
}
