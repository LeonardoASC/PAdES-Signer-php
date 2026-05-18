<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

use RuntimeException;

final readonly class OpenSslCmsVerifier
{
    public function verifyDetachedSignature(
        string $signedData,
        string $binarySignature
    ): bool {
        $tempDir = sys_get_temp_dir();

        $dataFile = tempnam($tempDir, 'pades-data-');
        $signatureFile = tempnam($tempDir, 'pades-signature-');

        if ($dataFile === false || $signatureFile === false) {
            throw new RuntimeException(
                'Não foi possível criar arquivos temporários.'
            );
        }

        file_put_contents($dataFile, $signedData);
        file_put_contents($signatureFile, $binarySignature);

        $result = openssl_pkcs7_verify(
            $signatureFile,
            PKCS7_NOVERIFY,
            null,
            [],
            null,
            $dataFile
        );

        @unlink($dataFile);
        @unlink($signatureFile);

        return $result === true || $result === 1;
    }
}
