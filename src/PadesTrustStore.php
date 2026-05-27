<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Crypto\X509\InMemoryTrustStore;
use NihilLabs\Pades\Crypto\X509\TrustStoreCredentialValidator;
use NihilLabs\Pades\Crypto\X509\TrustStoreInterface;
use NihilLabs\Pades\Exception\TrustStoreException;
use NihilLabs\Pades\Validation\TrustValidatorInterface;

final readonly class PadesTrustStore implements TrustStoreInterface
{
    private InMemoryTrustStore $trustStore;

    /**
     * @param array<string> $trustedCertificatesPem
     */
    public function __construct(array $trustedCertificatesPem = [])
    {
        $this->trustStore = new InMemoryTrustStore($trustedCertificatesPem);
    }

    public static function empty(): self
    {
        return new self();
    }

    public static function fromPem(string $certificatePem): self
    {
        return new self([$certificatePem]);
    }

    /**
     * @param array<string> $certificatesPem
     */
    public static function fromPemList(array $certificatesPem): self
    {
        return new self($certificatesPem);
    }

    public static function fromFile(string $path): self
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new TrustStoreException("Certificado confiavel nao encontrado ou ilegivel: {$path}");
        }

        $content = file_get_contents($path);

        if ($content === false) {
            throw new TrustStoreException("Certificado confiavel nao encontrado ou ilegivel: {$path}");
        }

        return new self([$content]);
    }

    /**
     * @param array<string> $paths
     */
    public static function fromFiles(array $paths): self
    {
        $certificates = [];

        foreach ($paths as $path) {
            if (! is_file($path) || ! is_readable($path)) {
                throw new TrustStoreException("Certificado confiavel nao encontrado ou ilegivel: {$path}");
            }

            $content = file_get_contents($path);

            if ($content === false) {
                throw new TrustStoreException("Certificado confiavel nao encontrado ou ilegivel: {$path}");
            }

            $certificates[] = $content;
        }

        return new self($certificates);
    }

    public static function fromDirectory(string $directory): self
    {
        $entries = scandir($directory);

        if ($entries === false) {
            throw new TrustStoreException("Diretorio de trust store nao encontrado ou ilegivel: {$directory}");
        }

        $certificates = [];

        foreach ($entries as $entry) {
            $path = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;

            if ($entry === '.' || $entry === '..' || ! is_file($path)) {
                continue;
            }

            $content = file_get_contents($path);

            if ($content === false) {
                throw new TrustStoreException("Certificado confiavel nao encontrado ou ilegivel: {$path}");
            }

            $certificates[] = $content;
        }

        return new self($certificates);
    }

    /**
     * @return array<string>
     */
    public function getTrustedCertificatesPem(): array
    {
        return $this->trustStore->getTrustedCertificatesPem();
    }

    public function credentialValidator(): TrustValidatorInterface
    {
        return new TrustStoreCredentialValidator($this);
    }
}
