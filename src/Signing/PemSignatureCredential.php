<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

use RuntimeException;

final readonly class PemSignatureCredential implements PrivateKeySignatureCredentialInterface
{
    private string $certificatePem;

    /**
     * @var array<string>
     */
    private array $certificateChainPem;

    private \OpenSSLAsymmetricKey $privateKey;

    /**
     * @param array<string> $certificateChainPem
     */
    public function __construct(
        string $certificatePem,
        string|\OpenSSLAsymmetricKey $privateKeyPemOrKey,
        ?string $privateKeyPassword = null,
        array $certificateChainPem = []
    ) {
        $this->certificatePem = $this->normalizeCertificate($certificatePem);
        $this->certificateChainPem = [
            $this->certificatePem,
            ...array_map(
                fn (string $pem): string => $this->normalizeCertificate($pem),
                $certificateChainPem
            ),
        ];
        $this->privateKey = $privateKeyPemOrKey instanceof \OpenSSLAsymmetricKey
            ? $privateKeyPemOrKey
            : $this->loadPrivateKey($privateKeyPemOrKey, $privateKeyPassword);
    }

    /**
     * @param array<string> $certificateChainPaths
     */
    public static function fromFiles(
        string $certificatePath,
        string $privateKeyPath,
        ?string $privateKeyPassword = null,
        array $certificateChainPaths = []
    ): self {
        return new self(
            certificatePem: self::readFile($certificatePath, 'certificado PEM'),
            privateKeyPemOrKey: self::readFile($privateKeyPath, 'chave privada PEM'),
            privateKeyPassword: $privateKeyPassword,
            certificateChainPem: array_map(
                fn (string $path): string => self::readFile($path, 'certificado PEM da cadeia'),
                $certificateChainPaths
            )
        );
    }

    public function getPrivateKey(): \OpenSSLAsymmetricKey
    {
        return $this->privateKey;
    }

    public function getCertificatePem(): string
    {
        return $this->certificatePem;
    }

    public function getCertificateChainPem(): array
    {
        return $this->certificateChainPem;
    }

    private function loadPrivateKey(
        string $privateKeyPem,
        ?string $privateKeyPassword
    ): \OpenSSLAsymmetricKey {
        $privateKey = openssl_pkey_get_private(
            $privateKeyPem,
            $privateKeyPassword ?? ''
        );

        if ($privateKey === false) {
            throw new RuntimeException(
                'Nao foi possivel carregar a chave privada PEM.'
            );
        }

        return $privateKey;
    }

    private function normalizeCertificate(string $certificatePem): string
    {
        $certificate = openssl_x509_read($certificatePem);

        if ($certificate === false) {
            throw new RuntimeException(
                'Nao foi possivel carregar o certificado PEM.'
            );
        }

        $exported = '';

        if (! openssl_x509_export($certificate, $exported)) {
            throw new RuntimeException(
                'Nao foi possivel normalizar o certificado PEM.'
            );
        }

        return $exported;
    }

    private static function readFile(string $path, string $description): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("Arquivo de {$description} nao encontrado: {$path}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Nao foi possivel ler o arquivo de {$description}: {$path}");
        }

        return $content;
    }
}
