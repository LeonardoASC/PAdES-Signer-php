<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Validation;

use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use NihilLabs\Pades\Crypto\Asn1\DerReader;
use NihilLabs\Pades\Crypto\X509\CertificateFormatNormalizer;
use NihilLabs\Pades\Crypto\X509\X509NameDerExtractor;
use NihilLabs\Pades\Internal\Crypto\CmsSignedData;
use NihilLabs\Pades\Internal\Crypto\CmsSignedDataParser;
use RuntimeException;

final readonly class CmsCadesValidator
{
    private const string CONTENT_TYPE = '1.2.840.113549.1.9.3';
    private const string MESSAGE_DIGEST = '1.2.840.113549.1.9.4';
    private const string SIGNING_TIME = '1.2.840.113549.1.9.5';
    private const string SIGNING_CERTIFICATE_V2 = '1.2.840.113549.1.9.16.2.47';
    private const string DATA = '1.2.840.113549.1.7.1';

    public function validate(
        string $cmsDer,
        string $signedData,
        SignatureAlgorithmPolicy $policy = new SignatureAlgorithmPolicy(),
        bool $allowSigningTime = true
    ): CmsValidationResult {
        try {
            $cms = (new CmsSignedDataParser())->parse($cmsDer);
            $signerCertificate = (new \NihilLabs\Pades\Internal\Crypto\CmsSignerCertificateExtractor())->extract($cmsDer);
        } catch (RuntimeException $exception) {
            return new CmsValidationResult(
                valid: false,
                checks: ['cms_parseable' => false],
                messages: [$exception->getMessage()],
                digestAlgorithm: null,
                signatureAlgorithm: null
            );
        }

        $messageDigest = $this->messageDigest($cms);
        $digestAlgorithm = $this->digestAlgorithmName($cms->signerDigestAlgorithmOid);
        $calculatedDigest = $digestAlgorithm === null ? null : hash($digestAlgorithm, $signedData, binary: true);
        $ess = $this->essCertIdV2($cms);
        $signatureAlgorithm = $this->signatureAlgorithmName($cms->signatureAlgorithmOid);
        $checks = [
            'cms_parseable' => true,
            'signed_data_content_type' => $cms->contentTypeOid === '1.2.840.113549.1.7.2',
            'encap_content_type_data' => $cms->encapContentTypeOid === self::DATA,
            'content_type_attribute' => $this->contentTypeAttribute($cms) === self::DATA,
            'message_digest_attribute' => $messageDigest !== null,
            'message_digest_matches_content' => $messageDigest !== null
                && $calculatedDigest !== null
                && hash_equals($calculatedDigest, $messageDigest),
            'signing_certificate_v2_attribute' => $ess !== null,
            'ess_cert_id_v2_matches_signer_certificate' => $ess !== null
                && $signerCertificate->certificateDer !== null
                && hash_equals($ess['certHash'], hash('sha256', $signerCertificate->certificateDer, binary: true)),
            'issuer_serial_matches_signer_certificate' => $ess !== null
                && $signerCertificate->certificatePem !== null
                && $this->issuerSerialMatches($ess['encoded'], $signerCertificate->certificatePem),
            'signer_info_matches_certificate' => $signerCertificate->matchesSignerInfo,
            'digest_algorithm_policy' => $digestAlgorithm === $policy->hashAlgorithm,
            'signature_algorithm_policy' => $signatureAlgorithm === $policy->signatureAlgorithm,
            'signature_algorithm_parameters' => $this->signatureParametersValid($cms, $policy),
            'optional_signed_attributes_supported' => $this->optionalAttributesSupported($cms),
            'signing_time_policy' => $allowSigningTime || ! isset($cms->signedAttributes[self::SIGNING_TIME]),
        ];

        $messages = [];

        foreach ($checks as $name => $passed) {
            if (! $passed) {
                $messages[] = "Falha na validacao CMS/CAdES: {$name}.";
            }
        }

        return new CmsValidationResult(
            valid: $messages === [],
            checks: $checks,
            messages: $messages,
            digestAlgorithm: $digestAlgorithm,
            signatureAlgorithm: $signatureAlgorithm
        );
    }

    private function contentTypeAttribute(CmsSignedData $cms): ?string
    {
        $attribute = $cms->signedAttribute(self::CONTENT_TYPE);

        if ($attribute === null) {
            return null;
        }

        $values = (new DerReader())->children($attribute->valuesEncoded);

        return isset($values[0]) && $values[0]['tag'] === 0x06
            ? \NihilLabs\Pades\Internal\Crypto\CmsOid::decode($values[0]['content'])
            : null;
    }

    private function messageDigest(CmsSignedData $cms): ?string
    {
        $attribute = $cms->signedAttribute(self::MESSAGE_DIGEST);

        if ($attribute === null) {
            return null;
        }

        $values = (new DerReader())->children($attribute->valuesEncoded);

        return isset($values[0]) && $values[0]['tag'] === 0x04
            ? $values[0]['content']
            : null;
    }

    /**
     * @return array{certHash:string,encoded:string}|null
     */
    private function essCertIdV2(CmsSignedData $cms): ?array
    {
        $attribute = $cms->signedAttribute(self::SIGNING_CERTIFICATE_V2);

        if ($attribute === null) {
            return null;
        }

        $values = (new DerReader())->children($attribute->valuesEncoded);
        $signingCertificate = $values[0] ?? null;

        if ($signingCertificate === null) {
            return null;
        }

        $fields = (new DerReader())->children($signingCertificate['encoded']);
        $certs = isset($fields[0]) ? (new DerReader())->children($fields[0]['encoded']) : [];
        $ess = $certs[0] ?? null;

        if ($ess === null) {
            return null;
        }

        foreach ((new DerReader())->children($ess['encoded']) as $field) {
            if ($field['tag'] === 0x04) {
                return [
                    'certHash' => $field['content'],
                    'encoded' => $ess['encoded'],
                ];
            }
        }

        return null;
    }

    private function issuerSerialMatches(string $essEncoded, string $certificatePem): bool
    {
        $extractor = new X509NameDerExtractor();

        return str_contains($essEncoded, $extractor->extractIssuerNameDer($certificatePem))
            && str_contains($essEncoded, $extractor->extractSerialNumberDer($certificatePem));
    }

    private function digestAlgorithmName(string $oid): ?string
    {
        return match ($oid) {
            '2.16.840.1.101.3.4.2.1' => SignatureAlgorithmPolicy::HASH_SHA256,
            '2.16.840.1.101.3.4.2.2' => SignatureAlgorithmPolicy::HASH_SHA384,
            '2.16.840.1.101.3.4.2.3' => SignatureAlgorithmPolicy::HASH_SHA512,
            default => null,
        };
    }

    private function signatureAlgorithmName(string $oid): ?string
    {
        return match ($oid) {
            '1.2.840.113549.1.1.10' => SignatureAlgorithmPolicy::SIGNATURE_RSA_PSS,
            '1.2.840.113549.1.1.11',
            '1.2.840.113549.1.1.12',
            '1.2.840.113549.1.1.13' => SignatureAlgorithmPolicy::SIGNATURE_RSA,
            '1.2.840.10045.4.3.2',
            '1.2.840.10045.4.3.3',
            '1.2.840.10045.4.3.4' => SignatureAlgorithmPolicy::SIGNATURE_ECDSA,
            default => null,
        };
    }

    private function signatureParametersValid(CmsSignedData $cms, SignatureAlgorithmPolicy $policy): bool
    {
        if ($policy->signatureAlgorithm === SignatureAlgorithmPolicy::SIGNATURE_RSA) {
            return $cms->signatureAlgorithmParameters === "\x05\x00";
        }

        if ($policy->signatureAlgorithm === SignatureAlgorithmPolicy::SIGNATURE_ECDSA) {
            return $cms->signatureAlgorithmParameters === null;
        }

        if ($policy->signatureAlgorithm !== SignatureAlgorithmPolicy::SIGNATURE_RSA_PSS) {
            return false;
        }

        return $cms->signatureAlgorithmOid === '1.2.840.113549.1.1.10'
            && $cms->signatureAlgorithmParameters !== null
            && str_contains($cms->signatureAlgorithmParameters, $this->pssHashOidMarker($policy->hashAlgorithm))
            && str_contains($cms->signatureAlgorithmParameters, $this->pssSaltLengthMarker($policy->hashAlgorithm));
    }

    private function pssHashOidMarker(string $hashAlgorithm): string
    {
        return match ($hashAlgorithm) {
            SignatureAlgorithmPolicy::HASH_SHA256 => hex2bin('608648016503040201'),
            SignatureAlgorithmPolicy::HASH_SHA384 => hex2bin('608648016503040202'),
            SignatureAlgorithmPolicy::HASH_SHA512 => hex2bin('608648016503040203'),
        };
    }

    private function pssSaltLengthMarker(string $hashAlgorithm): string
    {
        return match ($hashAlgorithm) {
            SignatureAlgorithmPolicy::HASH_SHA256 => "\x02\x01\x20",
            SignatureAlgorithmPolicy::HASH_SHA384 => "\x02\x01\x30",
            SignatureAlgorithmPolicy::HASH_SHA512 => "\x02\x01\x40",
        };
    }

    private function optionalAttributesSupported(CmsSignedData $cms): bool
    {
        $known = [
            self::CONTENT_TYPE,
            self::MESSAGE_DIGEST,
            self::SIGNING_TIME,
            self::SIGNING_CERTIFICATE_V2,
        ];

        foreach (array_keys($cms->signedAttributes) as $oid) {
            if (! in_array($oid, $known, true)) {
                return false;
            }
        }

        return true;
    }
}
