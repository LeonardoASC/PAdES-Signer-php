<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Laravel;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Timestamp\HttpTimestampClient;
use NihilLabs\Pades\Pades;
use NihilLabs\Pades\PadesLtvEnricher;
use NihilLabs\Pades\PadesReport;
use NihilLabs\Pades\PadesSignatureOptions;
use NihilLabs\Pades\PadesSignatureResult;
use NihilLabs\Pades\PadesTrustStore;
use NihilLabs\Pades\PadesValidationResult;
use NihilLabs\Pades\PadesValidator;
use RuntimeException;

final readonly class PadesManager
{
    public function __construct(
        private ConfigRepository $config
    ) {}

    public function sign(
        string $inputPdf,
        string $outputPdf,
        string $certificatePassword,
        ?PadesSignatureOptions $options = null,
        ?string $certificatePath = null
    ): PadesSignatureResult {
        $certificatePath ??= $this->configString('pades.certificate.path');

        if ($certificatePath === null || $certificatePath === '') {
            throw new InvalidArgumentException('Configure pades.certificate.path ou informe $certificatePath.');
        }

        return Pades::sign(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            certificatePath: $certificatePath,
            certificatePassword: $certificatePassword,
            options: $options ?? $this->signatureOptions()
        );
    }

    public function signWithPfxContents(
        string $inputPdf,
        string $outputPdf,
        string $certificateContents,
        string $certificatePassword,
        ?PadesSignatureOptions $options = null
    ): PadesSignatureResult {
        return Pades::signWithPfxContents(
            inputPdf: $inputPdf,
            outputPdf: $outputPdf,
            certificateContents: $certificateContents,
            certificatePassword: $certificatePassword,
            options: $options ?? $this->signatureOptions()
        );
    }

    /**
     * @param array<string, mixed> $overrides
     */
    public function signatureOptions(array $overrides = []): PadesSignatureOptions
    {
        $trustStore = $this->trustStore();
        $tsaUrl = $this->configString('pades.tsa.url');

        return new PadesSignatureOptions(
            timestampProvider: $tsaUrl === null || $tsaUrl === ''
                ? null
                : new HttpTimestampClient(
                    url: $tsaUrl,
                    timeoutSeconds: $this->configInt('pades.tsa.timeout', 15)
                ),
            trustValidator: $trustStore?->credentialValidator(),
            visibleSignature: (bool) ($overrides['visibleSignature'] ?? $this->configBool('pades.signature.visible', true)),
            signatureRect: $this->arrayConfigOrOverride($overrides, 'signatureRect', 'pades.signature.rect', [48, 48, 547, 96]),
            signatureFlags: (int) ($overrides['signatureFlags'] ?? $this->configInt('pades.signature.flags', 132)),
            signatureName: (string) ($overrides['signatureName'] ?? ''),
            signatureReason: (string) ($overrides['signatureReason'] ?? ''),
            signatureLocation: $this->nullableString($overrides['signatureLocation'] ?? null),
            signatureContactInfo: $this->nullableString($overrides['signatureContactInfo'] ?? null),
            signatureFieldName: $this->nullableString($overrides['signatureFieldName'] ?? null),
            signatureType: (string) ($overrides['signatureType'] ?? $this->configString('pades.signature.type', 'approval')),
            certificationPermission: (int) ($overrides['certificationPermission'] ?? $this->configInt('pades.signature.certification_permission', 2)),
            lockedFieldNames: $this->arrayConfigOrOverride($overrides, 'lockedFieldNames', 'pades.signature.locked_field_names', []),
            fieldLockAction: (string) ($overrides['fieldLockAction'] ?? $this->configString('pades.signature.field_lock_action', 'Include')),
            hashAlgorithm: (string) ($overrides['hashAlgorithm'] ?? $this->configString('pades.signature.hash_algorithm', 'sha256')),
            signatureAlgorithm: (string) ($overrides['signatureAlgorithm'] ?? $this->configString('pades.signature.signature_algorithm', 'rsa')),
            minimumHashAlgorithm: (string) ($overrides['minimumHashAlgorithm'] ?? $this->configString('pades.signature.minimum_hash_algorithm', 'sha256')),
            includeSigningTime: (bool) ($overrides['includeSigningTime'] ?? $this->configBool('pades.signature.include_signing_time', false)),
            appendSignaturePage: (bool) ($overrides['appendSignaturePage'] ?? $this->configBool('pades.signature.append_signature_page', true)),
            signaturePageMediaBox: $this->arrayConfigOrOverride($overrides, 'signaturePageMediaBox', 'pades.signature.page_media_box', [0, 0, 595, 842]),
            signaturePageRect: $this->arrayConfigOrOverride($overrides, 'signaturePageRect', 'pades.signature.page_rect', [48, 120, 547, 700])
        );
    }

    public function validator(): PadesValidator
    {
        $trustStore = $this->trustStore();

        if (
            $trustStore !== null
            && $this->configBool('pades.tsa.validate_with_trust_store', false)
        ) {
            return PadesValidator::withTsaTrustStore($trustStore);
        }

        return new PadesValidator();
    }

    public function validateFile(string $pdfPath): PadesValidationResult
    {
        return $this->validator()->validateFile($pdfPath);
    }

    public function report(): PadesReport
    {
        return new PadesReport($this->validator());
    }

    public function ltvEnricher(): PadesLtvEnricher
    {
        return new PadesLtvEnricher();
    }

    public function trustStore(): ?PadesTrustStore
    {
        if (! $this->configBool('pades.trust_store.enabled', false)) {
            return null;
        }

        $certificates = $this->configArray('pades.trust_store.certificates');
        $paths = $this->configArray('pades.trust_store.paths');
        $directory = $this->configString('pades.trust_store.directory');

        foreach ($paths as $path) {
            if (! is_string($path)) {
                continue;
            }

            $content = file_get_contents($path);

            if ($content === false) {
                throw new InvalidArgumentException("Certificado confiavel nao encontrado ou ilegivel: {$path}");
            }

            $certificates[] = $content;
        }

        if ($directory !== null && $directory !== '') {
            foreach (PadesTrustStore::fromDirectory($directory)->getTrustedCertificatesPem() as $certificatePem) {
                $certificates[] = $certificatePem;
            }
        }

        return PadesTrustStore::fromPemList(
            array_values(array_filter(
                $certificates,
                static fn (mixed $certificate): bool => is_string($certificate) && trim($certificate) !== ''
            ))
        );
    }

    private function configString(string $key, ?string $default = null): ?string
    {
        $value = $this->config->get($key, $default);

        return is_string($value) ? $value : $default;
    }

    private function configInt(string $key, int $default): int
    {
        $value = $this->config->get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    private function configBool(string $key, bool $default): bool
    {
        $value = $this->config->get($key, $default);

        return is_bool($value) ? $value : filter_var($value, FILTER_VALIDATE_BOOL);
    }

    /**
     * @return array<mixed>
     */
    private function configArray(string $key): array
    {
        $value = $this->config->get($key, []);

        return is_array($value) ? $value : [];
    }

    /**
     * @param array<string, mixed> $overrides
     * @param array<mixed> $default
     * @return array<mixed>
     */
    private function arrayConfigOrOverride(
        array $overrides,
        string $overrideKey,
        string $configKey,
        array $default
    ): array {
        if (isset($overrides[$overrideKey])) {
            if (! is_array($overrides[$overrideKey])) {
                throw new RuntimeException("Opcao {$overrideKey} deve ser array.");
            }

            return $overrides[$overrideKey];
        }

        $configured = $this->config->get($configKey, $default);

        return is_array($configured) ? $configured : $default;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}
