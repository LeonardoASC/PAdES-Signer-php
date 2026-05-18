<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

use NihilLabs\Pades\Certificate\PfxCertificate;
use RuntimeException;

final readonly class CmsSigner
{
    public function __construct(
        private PfxCertificate $certificate
    ) {}

    public function signDetached(string $data): string
    {
        $inputFile = tempnam(sys_get_temp_dir(), 'pades_input_');
        $outputFile = tempnam(sys_get_temp_dir(), 'pades_output_');

        if ($inputFile === false || $outputFile === false) {
            throw new RuntimeException('Não foi possível criar arquivos temporários.');
        }

        file_put_contents($inputFile, $data);

        $success = openssl_pkcs7_sign(
            input_filename: $inputFile,
            output_filename: $outputFile,
            certificate: $this->certificate->getPublicCertificate(),
            private_key: $this->certificate->getPrivateKey(),
            headers: [],
            flags: PKCS7_BINARY | PKCS7_DETACHED
        );

        if (! $success) {
            @unlink($inputFile);
            @unlink($outputFile);

            throw new RuntimeException('Não foi possível gerar assinatura CMS/PKCS#7.');
        }

        $signed = file_get_contents($outputFile);

        @unlink($inputFile);
        @unlink($outputFile);

        if ($signed === false) {
            throw new RuntimeException('Não foi possível ler a assinatura CMS/PKCS#7.');
        }

        return $signed;
    }

    public function signDetachedDer(string $data): string
    {
        $smime = $this->signDetached($data);

        if (! preg_match(
            '/Content-Transfer-Encoding:\s*base64\s+Content-Disposition:.*?\s+([A-Za-z0-9+\/=\r\n]+)\s+------/s',
            $smime,
            $matches
        )) {
            throw new RuntimeException('Não foi possível extrair a assinatura PKCS#7.');
        }

        $base64 = preg_replace('/\s+/', '', $matches[1]);

        if ($base64 === null) {
            throw new RuntimeException('Não foi possível normalizar a assinatura PKCS#7.');
        }

        $der = base64_decode($base64, strict: true);

        if ($der === false) {
            throw new RuntimeException('Não foi possível converter a assinatura PKCS#7 para DER.');
        }

        return $der;
    }
}
