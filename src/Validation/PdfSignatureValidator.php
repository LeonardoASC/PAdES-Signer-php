<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Internal\Crypto\CmsMessageDigestExtractor;
use NihilLabs\Pades\Internal\Crypto\CmsSignerCertificateExtractor;
use NihilLabs\Pades\Internal\Crypto\PadesCmsVerifier;
use NihilLabs\Pades\Pdf\EmbeddedPdfSignatureExtractor;
use RuntimeException;

final readonly class PdfSignatureValidator
{
    public function __construct(
        private PadesCmsVerifier $cmsVerifier = new PadesCmsVerifier()
    ) {}

    public function validateFirst(string $pdfContent): PdfSignatureValidationReport
    {
        return $this->validateSignature(
            pdfContent: $pdfContent,
            signature: (new EmbeddedPdfSignatureExtractor())->extractFirst($pdfContent)
        );
    }

    /**
     * @return list<PdfSignatureValidationReport>
     */
    public function validateAll(string $pdfContent): array
    {
        $reports = [];

        foreach ((new EmbeddedPdfSignatureExtractor())->extractAll($pdfContent) as $signature) {
            $reports[] = $this->validateSignature($pdfContent, $signature);
        }

        return $reports;
    }

    private function validateSignature(
        string $pdfContent,
        \NihilLabs\Pades\Pdf\EmbeddedPdfSignature $signature
    ): PdfSignatureValidationReport {
        $byteRangeMatchesContents = $this->byteRangeMatchesContents($pdfContent, $signature);
        $byteRangeInsideDocument = $signature->signedRevisionEnd <= strlen($pdfContent);
        $messageDigest = null;
        $digestAlgorithm = null;
        $calculatedDigest = null;
        $signerCertificate = null;

        try {
            $messageDigest = (new CmsMessageDigestExtractor())->extract($signature->contentsDerWithoutPadding);
            $digestAlgorithm = (new CmsMessageDigestExtractor())->digestAlgorithmForLength(strlen($messageDigest));
            $calculatedDigest = $digestAlgorithm === null
                ? null
                : $signature->digest($digestAlgorithm);
        } catch (RuntimeException) {
        }

        try {
            $signerCertificate = (new CmsSignerCertificateExtractor())->extract($signature->contentsDerWithoutPadding);
        } catch (RuntimeException) {
        }

        $checks = [
            'signature_dictionary' => $signature->signatureDictionary !== '',
            'signature_field' => $signature->fieldObjectNumber > 0,
            'contents_present' => $signature->contentsDerWithoutPadding !== '',
            'byte_range_semantic' => $byteRangeMatchesContents && $byteRangeInsideDocument,
            'signed_revision_digest_calculated' => $calculatedDigest !== null,
            'cms_message_digest_present' => $messageDigest !== null,
            'cms_message_digest_matches' => $calculatedDigest !== null
                && $messageDigest !== null
                && hash_equals($calculatedDigest, $messageDigest),
            'cryptographic_signature' => $this->verifyCms($signature->contentsDerWithoutPadding, $signature->signedData),
            'signer_certificate_present' => $signerCertificate?->certificatePem !== null,
            'signer_certificate_matches_signer_info' => (bool) ($signerCertificate?->matchesSignerInfo ?? false),
            'covers_whole_document' => $signature->coversWholeDocument,
            'no_unsigned_tail' => ! $this->hasUnsignedTail($signature->uncoveredRanges),
        ];

        $criticalChecks = [
            'signature_dictionary',
            'signature_field',
            'contents_present',
            'byte_range_semantic',
            'signed_revision_digest_calculated',
            'cms_message_digest_present',
            'cms_message_digest_matches',
            'cryptographic_signature',
            'signer_certificate_present',
            'signer_certificate_matches_signer_info',
        ];
        $messages = [];

        foreach ($criticalChecks as $name) {
            if (! $checks[$name]) {
                $messages[] = "Falha na validacao da assinatura PDF: {$name}.";
            }
        }

        return new PdfSignatureValidationReport(
            signature: $signature,
            valid: $messages === [],
            checks: $checks,
            messages: $messages,
            digestAlgorithm: $digestAlgorithm,
            calculatedDigestHex: $calculatedDigest === null ? null : strtoupper(bin2hex($calculatedDigest)),
            cmsMessageDigestHex: $messageDigest === null ? null : strtoupper(bin2hex($messageDigest)),
            signerCertificatePem: $signerCertificate?->certificatePem
        );
    }

    private function byteRangeMatchesContents(string $pdfContent, object $signature): bool
    {
        $contentsStart = $signature->byteRange->start1 + $signature->byteRange->length1;
        $contentsEnd = $signature->byteRange->start2;

        return $signature->byteRange->start1 === 0
            && $contentsStart > 0
            && $contentsEnd > $contentsStart
            && isset($pdfContent[$contentsStart], $pdfContent[$contentsEnd - 1])
            && $pdfContent[$contentsStart] === '<'
            && $pdfContent[$contentsEnd - 1] === '>';
    }

    private function verifyCms(string $cmsDer, string $signedData): bool
    {
        try {
            return $this->cmsVerifier->verifyByteRangeSignature($cmsDer, $signedData);
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @param list<array{start:int,length:int,kind:string}> $ranges
     */
    private function hasUnsignedTail(array $ranges): bool
    {
        foreach ($ranges as $range) {
            if ($range['kind'] === 'unsigned_tail') {
                return true;
            }
        }

        return false;
    }
}
