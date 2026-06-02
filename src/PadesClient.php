<?php

declare(strict_types=1);

namespace NihilLabs\Pades;

use NihilLabs\Pades\Crypto\Timestamp\HttpTimestampClient;
use NihilLabs\Pades\Exception\InvalidPadesArgumentException;
use NihilLabs\Pades\Signing\PfxSignatureCredential;
use NihilLabs\Pades\Signing\SignatureCredentialInterface;
use NihilLabs\Pades\Timestamp\TimestampProviderInterface;
use NihilLabs\Pades\Validation\TrustValidatorInterface;

final readonly class PadesClient
{
    public function __construct(
        private ?SignatureCredentialInterface $credential = null,
        private PadesSignatureOptions $options = new PadesSignatureOptions(),
        private PadesSigner $signer = new PadesSigner(),
        private PadesValidator $validator = new PadesValidator()
    ) {}

    /**
     * @param array<string, mixed> $config
     */
    public static function fromConfig(array $config): self
    {
        $maxInputPdfBytes = self::optionalInt($config, 'max_input_pdf_bytes');
        $validator = $maxInputPdfBytes === null
            ? new PadesValidator()
            : PadesValidator::withMaxPdfBytes($maxInputPdfBytes);

        return new self(
            credential: self::credentialFromConfig($config),
            options: self::optionsFromConfig($config, $maxInputPdfBytes),
            validator: $validator
        );
    }

    public function sign(
        string $inputPdf,
        string $outputPdf,
        ?SignatureCredentialInterface $credential = null,
        ?PadesSignatureOptions $options = null
    ): PadesSignatureResult {
        return $this->signer->sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            credential: $credential ?? $this->credential,
            options: $options ?? $this->options
        );
    }

    public function validateFile(string $pdfPath): PadesValidationResult
    {
        return $this->validator->validateFile($pdfPath);
    }

    public function reportForFile(string $pdfPath): array
    {
        return (new PadesReport($this->validator))->forFile($pdfPath);
    }

    public function options(): PadesSignatureOptions
    {
        return $this->options;
    }

    public function credential(): ?SignatureCredentialInterface
    {
        return $this->credential;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function credentialFromConfig(array $config): ?SignatureCredentialInterface
    {
        $credential = $config['credential'] ?? null;

        if ($credential instanceof SignatureCredentialInterface) {
            return $credential;
        }

        $password = self::optionalString($config, 'certificate_password')
            ?? self::optionalString($config, 'pfx_password');

        $contents = self::optionalString($config, 'pfx_contents')
            ?? self::optionalString($config, 'certificate_contents');

        if ($contents !== null) {
            if ($password === null) {
                throw new InvalidPadesArgumentException('Informe certificate_password para usar pfx_contents.');
            }

            return PfxSignatureCredential::fromContents($contents, $password);
        }

        $path = self::optionalString($config, 'certificate_path')
            ?? self::optionalString($config, 'pfx_path');

        if ($path !== null) {
            if ($password === null) {
                throw new InvalidPadesArgumentException('Informe certificate_password para usar certificate_path.');
            }

            return new PfxSignatureCredential($path, $password);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function optionsFromConfig(
        array $config,
        ?int $maxInputPdfBytes
    ): PadesSignatureOptions {
        return new PadesSignatureOptions(
            timestampProvider: self::timestampProviderFromConfig($config),
            trustValidator: self::optionalInstance($config, 'trust_validator', TrustValidatorInterface::class),
            signerProvider: self::optionalInstance($config, 'signer_provider', \NihilLabs\Pades\Signing\SignerProviderInterface::class),
            visibleSignature: self::bool($config, 'visible_signature', false),
            signatureRect: self::rect($config, 'signature_rect', [48, 48, 547, 96]),
            signatureFlags: self::int($config, 'signature_flags', 132),
            signatureName: self::string($config, 'signature_name', 'PAdES Core'),
            signatureReason: self::string($config, 'signature_reason', 'Document signed digitally'),
            signatureLocation: self::optionalString($config, 'signature_location'),
            signatureContactInfo: self::optionalString($config, 'signature_contact_info'),
            signatureFieldName: self::optionalString($config, 'signature_field_name'),
            signatureType: self::string($config, 'signature_type', PadesSignatureOptions::SIGNATURE_TYPE_APPROVAL),
            certificationPermission: self::int($config, 'certification_permission', 2),
            lockedFieldNames: self::stringList($config, 'locked_field_names'),
            fieldLockAction: self::string($config, 'field_lock_action', PadesSignatureOptions::FIELD_LOCK_INCLUDE),
            hashAlgorithm: self::string($config, 'hash_algorithm', \NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy::HASH_SHA256),
            signatureAlgorithm: self::string($config, 'signature_algorithm', \NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy::SIGNATURE_RSA),
            minimumHashAlgorithm: self::string($config, 'minimum_hash_algorithm', \NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy::HASH_SHA256),
            maxInputPdfBytes: $maxInputPdfBytes,
            includeSigningTime: self::bool($config, 'include_signing_time', false),
            appendSignaturePage: self::bool($config, 'append_signature_page', false),
            signaturePageMediaBox: self::rect($config, 'signature_page_media_box', [0, 0, 595, 842]),
            signaturePageRect: self::rect($config, 'signature_page_rect', [48, 120, 547, 700])
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function timestampProviderFromConfig(array $config): ?TimestampProviderInterface
    {
        $provider = $config['timestamp_provider'] ?? null;

        if ($provider instanceof TimestampProviderInterface) {
            return $provider;
        }

        $url = self::optionalString($config, 'timestamp_url');

        return $url === null ? null : new HttpTimestampClient($url);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function optionalString(array $config, string $key): ?string
    {
        if (! array_key_exists($key, $config) || $config[$key] === null || $config[$key] === '') {
            return null;
        }

        if (! is_string($config[$key])) {
            throw new InvalidPadesArgumentException("Config {$key} deve ser string.");
        }

        return $config[$key];
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function string(array $config, string $key, string $default): string
    {
        return self::optionalString($config, $key) ?? $default;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function optionalInt(array $config, string $key): ?int
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            return null;
        }

        return self::int($config, $key, 0);
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function int(array $config, string $key, int $default): int
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            return $default;
        }

        if (! is_int($config[$key])) {
            throw new InvalidPadesArgumentException("Config {$key} deve ser int.");
        }

        return $config[$key];
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function bool(array $config, string $key, bool $default): bool
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            return $default;
        }

        if (! is_bool($config[$key])) {
            throw new InvalidPadesArgumentException("Config {$key} deve ser bool.");
        }

        return $config[$key];
    }

    /**
     * @param array<string, mixed> $config
     * @param array{0:int,1:int,2:int,3:int} $default
     * @return array{0:int,1:int,2:int,3:int}
     */
    private static function rect(array $config, string $key, array $default): array
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            return $default;
        }

        $rect = $config[$key];

        if (! is_array($rect) || count($rect) !== 4) {
            throw new InvalidPadesArgumentException("Config {$key} deve ser array com quatro inteiros.");
        }

        foreach ($rect as $value) {
            if (! is_int($value)) {
                throw new InvalidPadesArgumentException("Config {$key} deve conter somente inteiros.");
            }
        }

        return array_values($rect);
    }

    /**
     * @param array<string, mixed> $config
     * @return array<string>
     */
    private static function stringList(array $config, string $key): array
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            return [];
        }

        if (! is_array($config[$key])) {
            throw new InvalidPadesArgumentException("Config {$key} deve ser array de strings.");
        }

        foreach ($config[$key] as $value) {
            if (! is_string($value)) {
                throw new InvalidPadesArgumentException("Config {$key} deve conter somente strings.");
            }
        }

        return array_values($config[$key]);
    }

    /**
     * @template T of object
     * @param array<string, mixed> $config
     * @param class-string<T> $class
     * @return T|null
     */
    private static function optionalInstance(array $config, string $key, string $class): ?object
    {
        if (! array_key_exists($key, $config) || $config[$key] === null) {
            return null;
        }

        if (! $config[$key] instanceof $class) {
            throw new InvalidPadesArgumentException("Config {$key} deve implementar {$class}.");
        }

        return $config[$key];
    }
}
