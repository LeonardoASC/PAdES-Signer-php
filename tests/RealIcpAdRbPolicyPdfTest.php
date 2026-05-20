<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\Cades\IcpBrasilSignaturePolicy;
use NihilLabs\Pades\Crypto\IcpBrasil\IcpBrasilPolicyCache;
use NihilLabs\Pades\Crypto\IcpBrasil\IcpBrasilPolicyRepository;
use NihilLabs\Pades\Crypto\IcpBrasil\ResolvedIcpPolicy;
use NihilLabs\Pades\Crypto\OpenSslBinaryCmsVerifier;
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

final class RealIcpAdRbPolicyPdfTest extends TestCase
{
    private const SIGNATURE_RESERVED_BYTES = 65536;
    private const SIG_POLICY_ID_OID_HEX = '2A864886F70D010910020F';
    private const SIGNATURE_TIMESTAMP_TOKEN_OID_HEX = '2A864886F70D010910020E';

    public function test_it_signs_real_icp_pdf_with_resolved_ad_rb_policy_without_timestamp(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $this->ensureOutputDirectory();

        try {
            $resolvedPolicy = $this->repository()->resolveAdRbPdfPolicy();
        } catch (Throwable $exception) {
            $this->markTestSkipped(
                'Politica AD-RB PAdES ITI indisponivel: ' . $exception->getMessage()
            );
        }

        $input = RealIcpSignedPdfFixture::outputPath('icp-ad-rb-policy-input.pdf');
        $output = RealIcpSignedPdfFixture::outputPath('icp-ad-rb-policy-output.pdf');

        (new MinimalPdfGenerator())
            ->generate($input);

        $signedPdf = $this->signPdfWithPolicy(
            inputPdf: $input,
            certificate: RealIcpSignedPdfFixture::certificate(),
            policy: IcpBrasilSignaturePolicy::fromResolvedPolicy($resolvedPolicy)
        );

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
        $this->assertStringContainsString(hex2bin(self::SIG_POLICY_ID_OID_HEX), $cms);
        $this->assertStringNotContainsString(hex2bin(self::SIGNATURE_TIMESTAMP_TOKEN_OID_HEX), $cms);
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
            asn1Path: RealIcpSignedPdfFixture::outputPath('icp-ad-rb-policy-cms.asn1.txt')
        );

        $this->writeDebug(
            resolvedPolicy: $resolvedPolicy,
            cmsDer: $parts['cms']
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
            throw new RuntimeException('PDF ICP AD-RB de entrada invalido.');
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
            timestampClient: null,
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
            . "/Reason (Document signed digitally with resolved ICP AD-RB policy)\n"
            . ">>";
    }

    private function writeDebug(
        ResolvedIcpPolicy $resolvedPolicy,
        string $cmsDer
    ): void {
        file_put_contents(
            RealIcpSignedPdfFixture::outputPath('icp-ad-rb-policy-debug.json'),
            json_encode(
                [
                    'policy' => $resolvedPolicy->toArray(),
                    'cms' => [
                        'containsSigPolicyId' => str_contains(
                            $cmsDer,
                            hex2bin(self::SIG_POLICY_ID_OID_HEX)
                        ),
                        'containsSignatureTimeStampToken' => str_contains(
                            $cmsDer,
                            hex2bin(self::SIGNATURE_TIMESTAMP_TOKEN_OID_HEX)
                        ),
                    ],
                ],
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
    }

    private function writeCmsAsn1Dump(
        string $cmsDer,
        string $asn1Path
    ): void {
        $cmsPath = tempnam(sys_get_temp_dir(), 'pades-ad-rb-cms-');

        if (! is_string($cmsPath)) {
            throw new RuntimeException('Cannot create temporary CMS file.');
        }

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
