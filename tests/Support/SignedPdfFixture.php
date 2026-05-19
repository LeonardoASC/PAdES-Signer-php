<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests\Support;

use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Pdf\ByteRange;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use RuntimeException;

final readonly class SignedPdfFixture
{
    public static function signedPdfContent(
        string $name,
        ?TimestampClientInterface $timestampClient = null
    ): string {
        $output = self::signedPdfPath($name, $timestampClient);

        $pdf = file_get_contents($output);

        if ($pdf === false) {
            throw new RuntimeException("PDF assinado não encontrado: {$output}");
        }

        return $pdf;
    }

    public static function signedPdfPath(
        string $name,
        ?TimestampClientInterface $timestampClient = null
    ): string {
        self::ensureOutputDirectory();

        $safeName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $name);

        if ($safeName === null || $safeName === '') {
            $safeName = 'signed';
        }

        $input = self::outputPath("{$safeName}-input.pdf");
        $output = self::outputPath("{$safeName}-signed.pdf");

        (new MinimalPdfGenerator())
            ->generate($input);

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $output,
            certificatePath: self::certificatePath(),
            certificatePassword: '123456',
            timestampClient: $timestampClient
        );

        return $output;
    }

    /**
     * @return array{signedData:string,cms:string}
     */
    public static function detachedCmsParts(string $pdf): array
    {
        if (! preg_match(
            '/\/ByteRange\s*\[(\d+)\s+(\d+)\s+(\d+)\s+(\d+)\]/',
            $pdf,
            $matches
        )) {
            throw new RuntimeException('/ByteRange não encontrado.');
        }

        $byteRange = new ByteRange(
            start1: (int) $matches[1],
            length1: (int) $matches[2],
            start2: (int) $matches[3],
            length2: (int) $matches[4]
        );

        return [
            'signedData' => (new ByteRangeCalculator())
                ->extractSignedData($pdf, $byteRange),
            'cms' => (new PdfSignatureExtractor())
                ->extractBinarySignatureWithoutPadding($pdf),
        ];
    }

    public static function outputPath(string $file): string
    {
        return dirname(__DIR__) . '/Output/' . $file;
    }

    private static function certificatePath(): string
    {
        return dirname(__DIR__) . '/Fixtures/certificate.pfx';
    }

    private static function ensureOutputDirectory(): void
    {
        $directory = dirname(__DIR__) . '/Output';

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
}
