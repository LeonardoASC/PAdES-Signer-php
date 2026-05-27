<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Certificate;

use RuntimeException;

final readonly class PfxCertificate
{
    private array $certificates;

    public function __construct(
        private string $path,
        private string $password,
        private ?string $contents = null
    ) {
        $this->certificates = $this->load();
    }

    public static function fromContents(string $contents, string $password): self
    {
        return new self(
            path: '',
            password: $password,
            contents: $contents
        );
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

    /**
     * @return array<string>
     */
    public function getExtraCertificates(): array
    {
        $extraCertificates = $this->certificates['extracerts'] ?? [];

        if (is_string($extraCertificates)) {
            return [$extraCertificates];
        }

        return $extraCertificates;
    }

    /**
     * @return array<string>
     */
    public function getCertificateChain(): array
    {
        $all = [
            $this->getPublicCertificate(),
            ...$this->getExtraCertificates(),
        ];

        $parsed = [];

        foreach ($all as $pem) {
            $info = openssl_x509_parse($pem);

            if ($info === false) {
                continue;
            }

            $parsed[] = [
                'pem' => $pem,
                'subject' => $info['subject'] ?? [],
                'issuer' => $info['issuer'] ?? [],
            ];
        }

        $ordered = [];

        // começa pelo signer
        $current = $parsed[0];

        $ordered[] = $current['pem'];

        while (true) {
            $found = null;

            foreach ($parsed as $candidate) {
                if ($candidate['subject'] === $current['issuer']) {
                    $found = $candidate;
                    break;
                }
            }

            if ($found === null) {
                break;
            }

            // evita loop
            if (in_array($found['pem'], $ordered, true)) {
                break;
            }

            $ordered[] = $found['pem'];

            $current = $found;
        }

        return $ordered;
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
        $content = $this->contents;

        if ($content === null && ! file_exists($this->path)) {
            throw new RuntimeException(
                "Certificado não encontrado: {$this->path}"
            );
        }

        $content ??= file_get_contents($this->path);

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
    public function getSerialNumberHex(): string
    {
        $info = $this->getInfo();

        return strtoupper(
            $info['serialNumberHex'] ?? dechex((int) ($info['serialNumber'] ?? 0))
        );
    }
}
