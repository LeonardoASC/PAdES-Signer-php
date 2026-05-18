<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Certificate;

use RuntimeException;

final readonly class PfxCertificate
{
    private array $certificates;

    public function __construct(
        private string $path,
        private string $password
    ) {
        $this->certificates = $this->load();
    }

    public function getPrivateKey(): \OpenSSLAsymmetricKey
    {
        $privateKey = openssl_pkey_get_private(
            $this->certificates['pkey'],
            $this->password
        );

        if ($privateKey === false) {
            throw new RuntimeException(
                'Não foi possível obter a chave privada.'
            );
        }

        return $privateKey;
    }

    public function getPublicCertificate(): string
    {
        return $this->certificates['cert'];
    }

    public function getInfo(): array
    {
        $info = openssl_x509_parse(
            $this->certificates['cert']
        );

        if ($info === false) {
            throw new RuntimeException(
                'Não foi possível ler o certificado.'
            );
        }

        return $info;
    }

    public function getCommonName(): ?string
    {
        return $this->getInfo()['subject']['CN'] ?? null;
    }

    private function load(): array
    {
        if (! file_exists($this->path)) {
            throw new RuntimeException(
                "Certificado não encontrado: {$this->path}"
            );
        }

        $content = file_get_contents($this->path);

        if ($content === false) {
            throw new RuntimeException(
                'Não foi possível ler o certificado.'
            );
        }

        $certificates = [];

        $success = openssl_pkcs12_read(
            $content,
            $certificates,
            $this->password
        );

        if (! $success) {
            throw new RuntimeException(
                'Senha inválida ou certificado inválido.'
            );
        }

        return $certificates;
    }
}