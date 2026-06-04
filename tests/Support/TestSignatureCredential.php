<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests\Support;

use NihilLabs\Pades\Signing\SignatureCredentialInterface;

final readonly class TestSignatureCredential implements SignatureCredentialInterface
{
    public function getCertificatePem(): string
    {
        $certificate = file_get_contents(dirname(__DIR__) . '/Fixtures/unrelated-trust-anchor.fixture');

        if ($certificate === false) {
            throw new \RuntimeException('Certificado PEM de teste nao encontrado.');
        }

        return $certificate;
    }

    public function getCertificateChainPem(): array
    {
        return [];
    }
}
