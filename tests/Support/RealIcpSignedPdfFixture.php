<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests\Support;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Timestamp\HttpTimestampClient;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use RuntimeException;

final readonly class RealIcpSignedPdfFixture
{
    public const TIMESTAMP_TOKEN_OID_HEX = '2A864886F70D010910020E';

    public static function isAvailable(): bool
    {
        return file_exists(self::certificatePath())
            && self::certificatePassword() !== null;
    }

    public static function skipMessage(): string
    {
        return 'Certificado ICP ou ICP_PFX_PASSWORD nao configurados.';
    }

    public static function timestampedPdfPath(): string
    {
        if (! self::isAvailable()) {
            throw new RuntimeException(self::skipMessage());
        }

        self::ensureOutputDirectory();

        $input = self::outputPath('icp-timestamped-input.pdf');
        $output = self::outputPath('icp-timestamped-output.pdf');

        (new MinimalPdfGenerator())
            ->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: self::certificatePath(),
            certificatePassword: self::certificatePassword(),
            timestampClient: new HttpTimestampClient(
                url: 'http://timestamp.digicert.com',
                timeoutSeconds: 30
            )
        );

        return $output;
    }

    public static function certificate(): PfxCertificate
    {
        if (! self::isAvailable()) {
            throw new RuntimeException(self::skipMessage());
        }

        return new PfxCertificate(
            path: self::certificatePath(),
            password: self::certificatePassword()
        );
    }

    public static function timestampedPdfContent(): string
    {
        $pdf = file_get_contents(self::timestampedPdfPath());

        if ($pdf === false) {
            throw new RuntimeException('PDF ICP timestampado nao encontrado.');
        }

        return $pdf;
    }

    public static function outputPath(string $file): string
    {
        return dirname(__DIR__) . '/Output/' . $file;
    }

    public static function certificatePath(): string
    {
        return dirname(__DIR__) . '/Fixtures/icp-valid.pfx';
    }

    public static function certificatePassword(): ?string
    {
        $password = getenv('ICP_PFX_PASSWORD');

        if (! is_string($password) || $password === '') {
            return null;
        }

        return $password;
    }

    private static function ensureOutputDirectory(): void
    {
        $directory = dirname(__DIR__) . '/Output';

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
}
