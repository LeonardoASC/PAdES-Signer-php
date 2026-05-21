<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Internal\Crypto;

use RuntimeException;

final readonly class PadesCmsVerifier
{
    public function __construct(
        private ?string $opensslBinary = null
    ) {}

    public function verifyByteRangeSignature(
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
            '%s cms -verify -binary -inform DER -in %s -content %s -noverify -out %s 2>&1',
            escapeshellarg($this->resolveOpenSslBinary()),
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

    private function resolveOpenSslBinary(): string
    {
        if ($this->opensslBinary !== null) {
            return $this->opensslBinary;
        }

        $fromEnvironment = getenv('OPENSSL_BINARY');

        if (is_string($fromEnvironment) && $fromEnvironment !== '') {
            return $fromEnvironment;
        }

        foreach ($this->candidateBinaries() as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return 'openssl';
    }

    /**
     * @return array<string>
     */
    private function candidateBinaries(): array
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return [];
        }

        return [
            'C:\\Program Files\\Git\\mingw64\\bin\\openssl.exe',
            'C:\\Program Files\\Git\\usr\\bin\\openssl.exe',
            'C:\\OpenSSL-Win64\\bin\\openssl.exe',
            'C:\\OpenSSL-Win32\\bin\\openssl.exe',
        ];
    }
}
