<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

use RuntimeException;

final readonly class ExternalSignatureCredential implements SignatureCredentialInterface
{
    private string $certificatePem;

    /**
     * @var array<string>
     */
    private array $certificateChainPem;

    /**
     * @param array<string> $certificateChainPem
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        string $certificatePem,
        private string $keyReference,
        private string $storageType = 'external',
        array $certificateChainPem = [],
        private array $metadata = []
    ) {
        $this->certificatePem = $this->normalizeCertificate($certificatePem);
        $this->certificateChainPem = [
            $this->certificatePem,
            ...array_map(
                fn (string $pem): string => $this->normalizeCertificate($pem),
                $certificateChainPem
            ),
        ];
    }

    public static function hsm(
        string $certificatePem,
        string $keyReference,
        array $certificateChainPem = [],
        array $metadata = []
    ): self {
        return new self($certificatePem, $keyReference, 'hsm', $certificateChainPem, $metadata);
    }

    public static function pkcs11(
        string $certificatePem,
        string $keyReference,
        array $certificateChainPem = [],
        array $metadata = []
    ): self {
        return new self($certificatePem, $keyReference, 'pkcs11', $certificateChainPem, $metadata);
    }

    public static function smartcard(
        string $certificatePem,
        string $keyReference,
        array $certificateChainPem = [],
        array $metadata = []
    ): self {
        return new self($certificatePem, $keyReference, 'smartcard', $certificateChainPem, $metadata);
    }

    public static function cloudKms(
        string $certificatePem,
        string $keyReference,
        array $certificateChainPem = [],
        array $metadata = []
    ): self {
        return new self($certificatePem, $keyReference, 'cloud-kms', $certificateChainPem, $metadata);
    }

    public static function remote(
        string $certificatePem,
        string $keyReference,
        array $certificateChainPem = [],
        array $metadata = []
    ): self {
        return new self($certificatePem, $keyReference, 'remote-signing', $certificateChainPem, $metadata);
    }

    public function getCertificatePem(): string
    {
        return $this->certificatePem;
    }

    public function getCertificateChainPem(): array
    {
        return $this->certificateChainPem;
    }

    public function getKeyReference(): string
    {
        return $this->keyReference;
    }

    public function getStorageType(): string
    {
        return $this->storageType;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    private function normalizeCertificate(string $certificatePem): string
    {
        $certificate = openssl_x509_read($certificatePem);

        if ($certificate === false) {
            throw new RuntimeException(
                'Nao foi possivel carregar o certificado PEM da credencial externa.'
            );
        }

        $exported = '';

        if (! openssl_x509_export($certificate, $exported)) {
            throw new RuntimeException(
                'Nao foi possivel normalizar o certificado PEM da credencial externa.'
            );
        }

        return $exported;
    }
}
