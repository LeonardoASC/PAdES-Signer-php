<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

use NihilLabs\Pades\Certificate\PfxCertificate;

final readonly class PfxSignatureCredential implements PrivateKeySignatureCredentialInterface
{
    private PfxCertificate $certificate;

    public function __construct(
        string|PfxCertificate $pathOrCertificate,
        ?string $password = null
    ) {
        if ($pathOrCertificate instanceof PfxCertificate) {
            $this->certificate = $pathOrCertificate;
            return;
        }

        if ($password === null) {
            throw new \InvalidArgumentException(
                'A senha do certificado PFX/P12 e obrigatoria.'
            );
        }

        $this->certificate = new PfxCertificate(
            path: $pathOrCertificate,
            password: $password
        );
    }

    public static function fromContents(string $contents, string $password): self
    {
        return new self(
            PfxCertificate::fromContents(
                contents: $contents,
                password: $password
            )
        );
    }

    public function getPrivateKey(): \OpenSSLAsymmetricKey
    {
        return $this->certificate->getPrivateKey();
    }

    public function getCertificatePem(): string
    {
        return $this->certificate->getPublicCertificate();
    }

    public function getCertificateChainPem(): array
    {
        return $this->certificate->getCertificateChain();
    }

    public function getPfxCertificate(): PfxCertificate
    {
        return $this->certificate;
    }
}
