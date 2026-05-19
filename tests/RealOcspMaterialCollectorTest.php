<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\RealOcspMaterialCollector;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class RealOcspMaterialCollectorTest extends TestCase
{
    public function test_it_collects_real_icp_ocsp_material(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $certificate = RealIcpSignedPdfFixture::certificate();

        try {
            $material = (new RealOcspMaterialCollector())
                ->collect(
                    signerCertificatePem: $certificate->getPublicCertificate(),
                    candidateCertificatesPem: $certificate->getCertificateChain()
                );
        } catch (RuntimeException $exception) {
            if (str_contains($exception->getMessage(), 'URL OCSP nao encontrada')) {
                $this->markTestSkipped(
                    'Certificado ICP real nao possui AIA OCSP; usar CRL/LCR para LTV.'
                );
            }

            throw $exception;
        }

        $this->assertNotEmpty($material->ocspUrl);
        $this->assertNotEmpty($material->responseDer);
        $this->assertContains($material->status, ['good', 'revoked', 'unknown']);
        $this->assertIsArray($material->responderCertificatesDer);
        $this->assertNotEmpty($material->certificateChainPem);

        $this->writeDerAndAsn1Dump(
            der: $material->responseDer,
            derPath: RealIcpSignedPdfFixture::outputPath('real-ocsp-response.der'),
            asn1Path: RealIcpSignedPdfFixture::outputPath('real-ocsp-response.asn1.txt')
        );
    }

    private function writeDerAndAsn1Dump(
        string $der,
        string $derPath,
        string $asn1Path
    ): void {
        $directory = dirname($derPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($derPath, $der);

        $command = sprintf(
            '%s asn1parse -inform DER -in %s 2>&1',
            escapeshellarg($this->opensslBinary()),
            escapeshellarg($derPath)
        );

        exec($command, $output, $exitCode);

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
