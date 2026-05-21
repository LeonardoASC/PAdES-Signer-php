<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Internal\Crypto\PadesCmsSigner;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use NihilLabs\Pades\Pdf\FakePdfBuilder;
use NihilLabs\Pades\Pdf\PdfByteRangePlaceholder;
use NihilLabs\Pades\Pdf\PdfSignatureContents;
use NihilLabs\Pades\Pdf\PdfSignaturePlaceholder;
use PHPUnit\Framework\TestCase;

final class PdfSigningPipelineTest extends TestCase
{
    public function test_it_builds_a_signed_pdf_structure(): void
    {
        $contents = new PdfSignatureContents(
            reservedBytes: 8192
        );

        $placeholderHex = $contents->placeholder();

        $builder = new FakePdfBuilder();

        $pdf = $builder->build($placeholderHex);

        $signaturePlaceholder = new PdfSignaturePlaceholder();

        $range = $signaturePlaceholder->findContentsRange($pdf);

        $byteRangeCalculator = new ByteRangeCalculator();

        $byteRange = $byteRangeCalculator->calculate(
            pdfContent: $pdf,
            contentsStart: $range['start'],
            contentsEnd: $range['end']
        );

        $pdf = (new PdfByteRangePlaceholder())
            ->replace($pdf, $byteRange);

        $signedData = $byteRangeCalculator->extractSignedData(
            $pdf,
            $byteRange
        );

        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new PadesCmsSigner($certificate))
            ->signPdfByteRangeData($signedData);

        $hexSignature = $contents->encode($cms);

        $pdf = $signaturePlaceholder->replaceContents(
            $pdf,
            $hexSignature
        );

        $output = __DIR__ . '/Output/fake-signed.pdf';

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0777, true);
        }

        file_put_contents($output, $pdf);

        $this->assertFileExists($output);

        $this->assertStringContainsString(
            '/SubFilter /ETSI.CAdES.detached',
            $pdf
        );

        $this->assertStringContainsString(
            '/ByteRange [0 ',
            $pdf
        );

        $this->assertStringContainsString(
            '/Contents <',
            $pdf
        );

        $this->assertStringContainsString(
            strtoupper(bin2hex(substr($cms, 0, 8))),
            $pdf
        );
    }
}
