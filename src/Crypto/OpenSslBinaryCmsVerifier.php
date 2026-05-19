<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto;

use RuntimeException;

final readonly class OpenSslBinaryCmsVerifier
{
    public function verify(
        string $cmsDer,
        string $signedData
    ): bool {
        $cmsFile = tempnam(sys_get_temp_dir(), 'pades-cms-');
        $dataFile = tempnam(sys_get_temp_dir(), 'pades-data-');
        $outFile = tempnam(sys_get_temp_dir(), 'pades-out-');

        if ($cmsFile === false || $dataFile === false || $outFile === false) {
            throw new RuntimeException('Não foi possível criar arquivos temporários.');
        }

        file_put_contents($cmsFile, $cmsDer);
        file_put_contents($dataFile, $signedData);

        $command = sprintf(
            'openssl cms -verify -binary -inform DER -in %s -content %s -noverify -out %s 2>&1',
            escapeshellarg($cmsFile),
            escapeshellarg($dataFile),
            escapeshellarg($outFile)
        );

        exec($command, $output, $exitCode);

        @unlink($cmsFile);
        @unlink($dataFile);
        @unlink($outFile);

        return $exitCode === 0;
    }
}