<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;

final readonly class IssuerAndSerialNumber
{
    public function build(PfxCertificate $certificate): string
    {
        $extractor = new X509NameDerExtractor();
        $certificatePem = $certificate->getPublicCertificate();

        return Der::sequence(
            $extractor->extractIssuerNameDer($certificatePem)
                . $extractor->extractSerialNumberDer($certificatePem)
        );
    }
}
