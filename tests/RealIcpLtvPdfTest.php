<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Internal\Crypto\PadesCmsVerifier;
use NihilLabs\Pades\Pdf\PdfRealLtvEnricher;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class RealIcpLtvPdfTest extends TestCase
{
    public function test_it_enriches_real_icp_timestamped_pdf_with_ltv_dss_vri(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $signedPdfPath = RealIcpSignedPdfFixture::timestampedPdfPath();
        $signedPdf = file_get_contents($signedPdfPath);

        $this->assertNotFalse($signedPdf);

        $certificate = RealIcpSignedPdfFixture::certificate();

        $ltvPdf = (new PdfRealLtvEnricher())
            ->enrich(
                signedPdfContent: $signedPdf,
                signerCertificatePem: $certificate->getPublicCertificate(),
                candidateCertificatesPem: $certificate->getCertificateChain()
            );

        $output = RealIcpSignedPdfFixture::outputPath('icp-ltv-output.pdf');

        file_put_contents($output, $ltvPdf);

        $this->assertFileExists($output);
        $this->assertGreaterThan(0, filesize($output));
        $this->assertStringStartsWith($signedPdf, $ltvPdf);
        $this->assertStringContainsString('/DSS', $ltvPdf);
        $this->assertStringContainsString('/VRI', $ltvPdf);
        $this->assertStringContainsString('/OCSPs', $ltvPdf);
        $this->assertStringContainsString('/Certs', $ltvPdf);
        $this->assertStringContainsString('/CRLs', $ltvPdf);
        $this->assertStringContainsString('/ByteRange', $ltvPdf);

        $parts = SignedPdfFixture::detachedCmsParts($ltvPdf);

        $this->assertTrue(
            (new PadesCmsVerifier())
                ->verifyByteRangeSignature(
                    cmsDer: $parts['cms'],
                    signedData: $parts['signedData']
                )
        );

        $this->writeCmsAsn1Dump(
            cmsDer: $parts['cms'],
            asn1Path: RealIcpSignedPdfFixture::outputPath('icp-ltv-cms.asn1.txt')
        );
    }

    private function writeCmsAsn1Dump(
        string $cmsDer,
        string $asn1Path
    ): void {
        $directory = dirname($asn1Path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $cmsPath = tempnam(sys_get_temp_dir(), 'pades-ltv-cms-');

        $this->assertIsString($cmsPath);

        file_put_contents($cmsPath, $cmsDer);

        $command = sprintf(
            '%s asn1parse -inform DER -in %s 2>&1',
            escapeshellarg($this->opensslBinary()),
            escapeshellarg($cmsPath)
        );

        exec($command, $output, $exitCode);

        @unlink($cmsPath);

        file_put_contents($asn1Path, implode(PHP_EOL, $output));

        $this->assertSame(0, $exitCode);
        $this->assertFileExists($asn1Path);
    }

    private function opensslBinary(): string
    {
        $fromEnvironment = getenv('OPENSSL_BINARY');

        if (is_string($fromEnvironment) && $fromEnvironment !== '') {
            return $fromEnvironment;
        }

        if (PHP_OS_FAMILY === 'Windows') {
            foreach ([
                'C:\\Program Files\\Git\\mingw64\\bin\\openssl.exe',
                'C:\\Program Files\\Git\\usr\\bin\\openssl.exe',
                'C:\\OpenSSL-Win64\\bin\\openssl.exe',
                'C:\\OpenSSL-Win32\\bin\\openssl.exe',
            ] as $candidate) {
                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return 'openssl';
    }
}
