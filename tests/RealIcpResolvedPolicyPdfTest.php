<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\Cades\IcpBrasilSignaturePolicy;
use NihilLabs\Pades\Crypto\IcpBrasil\IcpBrasilPolicyCache;
use NihilLabs\Pades\Crypto\IcpBrasil\IcpBrasilPolicyRepository;
use NihilLabs\Pades\Crypto\IcpBrasil\LpaDownloader;
use NihilLabs\Pades\Crypto\IcpBrasil\LpaParser;
use NihilLabs\Pades\Crypto\IcpBrasil\ResolvedIcpPolicy;
use NihilLabs\Pades\Crypto\OpenSslBinaryCmsVerifier;
use NihilLabs\Pades\Crypto\Timestamp\HttpTimestampClient;
use NihilLabs\Pades\Pdf\ByteRangeCalculator;
use NihilLabs\Pades\Pdf\IncrementalPdfWriter;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\PdfAcroForm;
use NihilLabs\Pades\Pdf\PdfByteRangePlaceholder;
use NihilLabs\Pades\Pdf\PdfCatalogInspector;
use NihilLabs\Pades\Pdf\PdfCatalogUpdater;
use NihilLabs\Pades\Pdf\PdfObjectInspector;
use NihilLabs\Pades\Pdf\PdfPageInspector;
use NihilLabs\Pades\Pdf\PdfPageUpdater;
use NihilLabs\Pades\Pdf\PdfSignatureContents;
use NihilLabs\Pades\Pdf\PdfSignatureExtractor;
use NihilLabs\Pades\Pdf\PdfSignaturePlaceholder;
use NihilLabs\Pades\Pdf\PdfSignatureWidget;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use NihilLabs\Pades\Tests\Support\SignedPdfFixture;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class RealIcpResolvedPolicyPdfTest extends TestCase
{
    private const SIGNATURE_RESERVED_BYTES = 65536;

    public function test_it_signs_real_icp_pdf_with_resolved_iti_policy_timestamp_and_openssl_verification(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $this->ensureOutputDirectory();

        try {
            $resolvedPolicy = $this->repository()->resolveAdRtPdfPolicy();
        } catch (Throwable $exception) {
            $this->markTestSkipped(
                'Politica ICP-Brasil ITI indisponivel: ' . $exception->getMessage()
            );
        }

        $this->writePolicyDebug($resolvedPolicy);
        $this->writeLpaDebug();

        $input = RealIcpSignedPdfFixture::outputPath('icp-real-policy-input.pdf');
        $output = RealIcpSignedPdfFixture::outputPath('icp-real-policy-output.pdf');

        (new MinimalPdfGenerator())
            ->generate($input);

        try {
            $signedPdf = $this->signPdfWithPolicy(
                inputPdf: $input,
                certificate: RealIcpSignedPdfFixture::certificate(),
                policy: IcpBrasilSignaturePolicy::fromResolvedPolicy($resolvedPolicy)
            );
        } catch (Throwable $exception) {
            $this->markTestSkipped(
                'Assinatura real ICP com timestamp indisponivel: ' . $exception->getMessage()
            );
        }

        file_put_contents($output, $signedPdf);

        $this->assertFileExists($output);
        $this->assertStringContainsString('/ByteRange', $signedPdf);
        $this->assertStringContainsString('/ETSI.CAdES.detached', $signedPdf);

        $cms = (new PdfSignatureExtractor())
            ->extractBinarySignatureWithoutPadding($signedPdf);

        $this->assertStringContainsString(
            Der::oid($this->dottedOidToHex($resolvedPolicy->policyOid)),
            $cms
        );

        $this->assertStringContainsString($resolvedPolicy->policyHash, $cms);

        $parts = SignedPdfFixture::detachedCmsParts($signedPdf);

        $this->assertTrue(
            (new OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $parts['cms'],
                    signedData: $parts['signedData']
                )
        );

        $this->writeCmsAsn1Dump(
            cmsDer: $parts['cms'],
            asn1Path: RealIcpSignedPdfFixture::outputPath('icp-policy-cms.asn1.txt')
        );
    }

    private function repository(): IcpBrasilPolicyRepository
    {
        return new IcpBrasilPolicyRepository(
            cache: new IcpBrasilPolicyCache(
                directory: RealIcpSignedPdfFixture::outputPath('icp-policy-cache'),
                ttlSeconds: 86400
            )
        );
    }

    private function signPdfWithPolicy(
        string $inputPdf,
        PfxCertificate $certificate,
        IcpBrasilSignaturePolicy $policy
    ): string {
        $content = file_get_contents($inputPdf);

        if ($content === false || ! str_starts_with($content, '%PDF-')) {
            throw new RuntimeException('PDF ICP de entrada invalido.');
        }

        $objectInspector = new PdfObjectInspector();
        $nextObjectNumber = $objectInspector->getNextObjectNumber($content);
        $signatureObjectNumber = $nextObjectNumber;
        $widgetObjectNumber = $nextObjectNumber + 1;
        $acroFormObjectNumber = $nextObjectNumber + 2;

        $catalogInspector = new PdfCatalogInspector();
        $catalogNumber = $catalogInspector->getCatalogObjectNumber($content);
        $catalogBody = $catalogInspector->getCatalogObjectBody($content);

        $pageInspector = new PdfPageInspector();
        $pageNumber = $pageInspector->getFirstPageObjectNumber($content);
        $pageBody = $pageInspector->getFirstPageObjectBody($content);

        $updated = (new IncrementalPdfWriter())
            ->appendObjects(
                pdfContent: $content,
                objects: [
                    $signatureObjectNumber => $this->signatureObject(),
                    $widgetObjectNumber => (new PdfSignatureWidget())
                        ->build(
                            signatureObjectNumber: $signatureObjectNumber,
                            pageObjectNumber: $pageNumber
                        ),
                    $acroFormObjectNumber => (new PdfAcroForm())
                        ->build($widgetObjectNumber),
                    $catalogNumber => (new PdfCatalogUpdater())
                        ->addAcroForm(
                            catalogBody: $catalogBody,
                            acroFormObjectNumber: $acroFormObjectNumber
                        ),
                    $pageNumber => (new PdfPageUpdater())
                        ->addAnnotation(
                            pageBody: $pageBody,
                            widgetObjectNumber: $widgetObjectNumber
                        ),
                ]
            );

        return $this->applyPolicySignature(
            pdfContent: $updated,
            certificate: $certificate,
            policy: $policy
        );
    }

    private function applyPolicySignature(
        string $pdfContent,
        PfxCertificate $certificate,
        IcpBrasilSignaturePolicy $policy
    ): string {
        $signaturePlaceholder = new PdfSignaturePlaceholder();
        $contentsRange = $signaturePlaceholder->findContentsRange($pdfContent);
        $byteRangeCalculator = new ByteRangeCalculator();
        $byteRange = $byteRangeCalculator->calculate(
            pdfContent: $pdfContent,
            contentsStart: $contentsRange['start'],
            contentsEnd: $contentsRange['end']
        );

        $pdfContent = (new PdfByteRangePlaceholder())
            ->replace($pdfContent, $byteRange);

        $signedData = $byteRangeCalculator->extractSignedData(
            pdfContent: $pdfContent,
            byteRange: $byteRange
        );

        $cms = (new AdvancedCmsSigner(
            certificate: $certificate,
            timestampClient: new HttpTimestampClient(
                url: RealIcpSignedPdfFixture::timestampUrl(),
                timeoutSeconds: 30
            ),
            signaturePolicy: $policy
        ))->signDetachedDer($signedData);

        $hexSignature = (new PdfSignatureContents(
            reservedBytes: self::SIGNATURE_RESERVED_BYTES
        ))->encode($cms);

        return $signaturePlaceholder->replaceContents(
            pdfContent: $pdfContent,
            hexSignature: $hexSignature
        );
    }

    private function signatureObject(): string
    {
        $contents = new PdfSignatureContents(
            reservedBytes: self::SIGNATURE_RESERVED_BYTES
        );

        $date = gmdate('YmdHis');

        return "<<\n"
            . "/Type /Sig\n"
            . "/Filter /Adobe.PPKLite\n"
            . "/SubFilter /ETSI.CAdES.detached\n"
            . "/ByteRange [********** ********** ********** **********]\n"
            . "/Contents <" . $contents->placeholder() . ">\n"
            . "/M (D:{$date}+00'00')\n"
            . "/Name (PAdES Core)\n"
            . "/Reason (Document signed digitally with resolved ICP policy)\n"
            . ">>";
    }

    private function writePolicyDebug(ResolvedIcpPolicy $policy): void
    {
        file_put_contents(
            RealIcpSignedPdfFixture::outputPath('policy-debug.json'),
            json_encode(
                $policy->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
    }

    private function writeLpaDebug(): void
    {
        try {
            $bytes = (new LpaDownloader(timeoutSeconds: 10, retries: 0))
                ->downloadPadesLpa();

            $policies = array_map(
                static fn ($policy): array => $policy->toArray(),
                (new LpaParser())->parse($bytes)
            );

            $payload = ['policies' => $policies];
        } catch (Throwable $exception) {
            $payload = ['error' => $exception->getMessage()];
        }

        file_put_contents(
            RealIcpSignedPdfFixture::outputPath('lpa-debug.json'),
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
    }

    private function writeCmsAsn1Dump(
        string $cmsDer,
        string $asn1Path
    ): void {
        $cmsPath = tempnam(sys_get_temp_dir(), 'pades-policy-cms-');

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

    private function dottedOidToHex(string $oid): string
    {
        $parts = array_map('intval', explode('.', $oid));
        $encoded = chr(($parts[0] * 40) + $parts[1]);

        foreach (array_slice($parts, 2) as $part) {
            $encoded .= $this->base128($part);
        }

        return strtoupper(bin2hex($encoded));
    }

    private function base128(int $value): string
    {
        $bytes = [chr($value & 0x7F)];
        $value >>= 7;

        while ($value > 0) {
            array_unshift($bytes, chr(($value & 0x7F) | 0x80));
            $value >>= 7;
        }

        return implode('', $bytes);
    }

    private function ensureOutputDirectory(): void
    {
        $directory = dirname(RealIcpSignedPdfFixture::outputPath('placeholder'));

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }
}
