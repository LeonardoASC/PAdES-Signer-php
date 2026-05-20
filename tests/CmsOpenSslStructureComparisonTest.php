<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class CmsOpenSslStructureComparisonTest extends TestCase
{
    public function test_pades_core_cms_matches_openssl_detached_cms_structure_semantically(): void
    {
        $openssl = $this->opensslBinary();

        if ($openssl === null) {
            $this->markTestSkipped('OpenSSL CLI nao encontrado.');
        }

        $outputDirectory = __DIR__ . '/Output';

        if (! is_dir($outputDirectory)) {
            mkdir($outputDirectory, 0777, true);
        }

        $data = 'hello world';
        $dataFile = $outputDirectory . '/cms-compare-data.bin';
        $certFile = $outputDirectory . '/cms-compare-cert.pem';
        $keyFile = $outputDirectory . '/cms-compare-key.pem';
        $opensslCmsFile = $outputDirectory . '/cms-compare-openssl.der';
        $padesCmsFile = $outputDirectory . '/cms-compare-pades.der';

        $this->writePfxParts($certFile, $keyFile);

        file_put_contents($dataFile, $data);

        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        file_put_contents(
            $padesCmsFile,
            (new AdvancedCmsSigner($certificate))->signDetachedDer($data)
        );

        $this->runCommand(
            sprintf(
                '%s cms -sign -binary -in %s -signer %s -inkey %s -outform DER -out %s -nosmimecap -md sha256',
                escapeshellarg($openssl),
                escapeshellarg($dataFile),
                escapeshellarg($certFile),
                escapeshellarg($keyFile),
                escapeshellarg($opensslCmsFile)
            )
        );

        $opensslPrint = $this->cmsPrint($openssl, $opensslCmsFile);
        $padesPrint = $this->cmsPrint($openssl, $padesCmsFile);

        foreach ($this->commonExpectedFragments() as $fragment) {
            $this->assertStringContainsString($fragment, $opensslPrint);
            $this->assertStringContainsString($fragment, $padesPrint);
        }

        $this->assertStringContainsString(
            'unsignedAttrs:' . PHP_EOL . '          <ABSENT>',
            $opensslPrint
        );

        $this->assertStringContainsString(
            'unsignedAttrs:' . PHP_EOL . '          <ABSENT>',
            $padesPrint
        );

        $this->assertStringContainsString(
            'sha256WithRSAEncryption (1.2.840.113549.1.1.11)',
            $this->signerInfoSection($padesPrint)
        );

        $this->assertStringContainsString(
            'id-smime-aa-signingCertificateV2',
            $padesPrint
        );
    }

    /**
     * @return array<string>
     */
    private function commonExpectedFragments(): array
    {
        return [
            'contentType: pkcs7-signedData',
            'algorithm: sha256 (2.16.840.1.101.3.4.2.1)',
            'parameter: NULL',
            'eContentType: pkcs7-data',
            'eContent: <ABSENT>',
            'signatureAlgorithm:',
            'algorithm: sha256WithRSAEncryption (1.2.840.113549.1.1.11)',
            'object: contentType (1.2.840.113549.1.9.3)',
            'object: signingTime (1.2.840.113549.1.9.5)',
            'UTCTIME:',
            'object: messageDigest (1.2.840.113549.1.9.4)',
        ];
    }

    private function writePfxParts(
        string $certFile,
        string $keyFile
    ): void {
        $pfx = file_get_contents(__DIR__ . '/Fixtures/certificate.pfx');

        if ($pfx === false) {
            throw new RuntimeException('PFX de teste nao encontrado.');
        }

        $parts = [];

        if (! openssl_pkcs12_read($pfx, $parts, '123456')) {
            throw new RuntimeException('Nao foi possivel ler PFX de teste.');
        }

        file_put_contents($certFile, $parts['cert']);
        file_put_contents($keyFile, $parts['pkey']);
    }

    private function cmsPrint(
        string $openssl,
        string $cmsFile
    ): string {
        return $this->runCommand(
            sprintf(
                '%s cms -inform DER -in %s -cmsout -print',
                escapeshellarg($openssl),
                escapeshellarg($cmsFile)
            )
        );
    }

    private function signerInfoSection(string $cmsPrint): string
    {
        $position = strpos($cmsPrint, 'signerInfos:');

        if ($position === false) {
            return $cmsPrint;
        }

        return substr($cmsPrint, $position);
    }

    private function runCommand(string $command): string
    {
        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(implode(PHP_EOL, $output));
        }

        return implode(PHP_EOL, $output);
    }

    private function opensslBinary(): ?string
    {
        $fromEnvironment = getenv('OPENSSL_BINARY');

        if (is_string($fromEnvironment) && $fromEnvironment !== '') {
            return $fromEnvironment;
        }

        foreach ($this->candidateBinaries() as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        exec('openssl version 2>&1', $output, $exitCode);

        return $exitCode === 0 ? 'openssl' : null;
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
