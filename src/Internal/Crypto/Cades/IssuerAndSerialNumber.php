<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto\Cades;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;

final readonly class IssuerAndSerialNumber
{
    public function build(PfxCertificate|string $certificate): string
    {
        $extractor = new X509NameDerExtractor();
        $certificatePem = $certificate instanceof PfxCertificate
            ? $certificate->getPublicCertificate()
            : $certificate;

        return Der::sequence(
            $extractor->extractIssuerNameDer($certificatePem)
                . $extractor->extractSerialNumberDer($certificatePem)
        );
    }
}
