<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use NihilLabs\Pades\Signing\SignatureCredentialInterface;

final readonly class OpenSslCertificateChainValidator implements CertificateChainValidatorInterface
{
    public function __construct(
        private ?RealCertificateChainCollector $chainCollector = null,
        private ?CertificateFormatNormalizer $normalizer = null,
        private ?CertificatePathBuilder $pathBuilder = null,
        private ?CertificateValidationContext $context = null,
        private ?CertificateValidationPolicy $policy = null
    ) {}

    public function validateCredential(
        SignatureCredentialInterface $credential,
        TrustStoreInterface $trustStore
    ): CertificateChainValidationResult {
        return $this->validateCertificateChain(
            signerCertificatePem: $credential->getCertificatePem(),
            candidateCertificatesPem: $credential->getCertificateChainPem(),
            trustStore: $trustStore
        );
    }

    /**
     * @param array<string> $candidateCertificatesPem
     */
    public function validateCertificateChain(
        string $signerCertificatePem,
        array $candidateCertificatesPem,
        TrustStoreInterface $trustStore
    ): CertificateChainValidationResult {
        $collector = $this->chainCollector ?? new RealCertificateChainCollector();
        $chainData = $collector->collect(
            signerCertificatePem: $signerCertificatePem,
            candidateCertificatesPem: $candidateCertificatesPem
        );
        $collectedChain = $chainData['chain'];

        if ($collectedChain === []) {
            return new CertificateChainValidationResult(
                trusted: false,
                chainPem: [],
                messages: ['Cadeia X.509 vazia.']
            );
        }

        $signerValidation = (new X509CertificateValidator())->validate(
            certificatePem: $signerCertificatePem,
            context: $this->context ?? new CertificateValidationContext(),
            policy: $this->policy ?? new CertificateValidationPolicy()
        );

        if (! $signerValidation->valid) {
            return new CertificateChainValidationResult(
                trusted: false,
                chainPem: $collectedChain,
                messages: $signerValidation->messages,
                details: ['signer' => $signerValidation->details]
            );
        }

        $trustAnchors = $this->trustedCandidates($trustStore);
        $paths = ($this->pathBuilder ?? new CertificatePathBuilder())->buildPaths(
            signerCertificatePem: $signerCertificatePem,
            candidatesPem: [...$candidateCertificatesPem, ...array_slice($collectedChain, 1)],
            trustAnchorsPem: $trustAnchors
        );
        $messages = [];

        foreach ($paths as $candidateChain) {
            if (! $this->verifyLinks($candidateChain)) {
                $messages[] = 'Assinatura de um certificado da cadeia nao confere com seu emissor.';
                continue;
            }

            $caMessages = $this->validateCaCertificates($candidateChain);

            if ($caMessages !== []) {
                $messages = [...$messages, ...$caMessages];
                continue;
            }

            $trustAnchorPem = $candidateChain[count($candidateChain) - 1];

            return new CertificateChainValidationResult(
                trusted: true,
                chainPem: $candidateChain,
                trustAnchorPem: $trustAnchorPem,
                details: $this->chainDetails($candidateChain)
            );
        }

        return new CertificateChainValidationResult(
            trusted: false,
            chainPem: $collectedChain,
            messages: $messages !== []
                ? array_values(array_unique($messages))
                : ['Cadeia X.509 nao ancora em um certificado confiavel do trust store.'],
            details: [
                'candidate_paths' => count($paths),
                'trust_anchors' => count($trustAnchors),
            ]
        );
    }

    /**
     * @return array<string>
     */
    private function trustedCandidates(TrustStoreInterface $trustStore): array
    {
        return $trustStore->getTrustedCertificatesPem();
    }

    /**
     * @param array<string> $chain
     */
    private function verifyLinks(array $chain): bool
    {
        for ($i = 0; $i < count($chain) - 1; $i++) {
            if ($this->verifyCertificateWithIssuer($chain[$i], $chain[$i + 1]) !== 1) {
                return false;
            }
        }

        return true;
    }

    private function verifyCertificateWithIssuer(string $certificatePem, string $issuerPem): int
    {
        $certificate = openssl_x509_read($certificatePem);
        $issuer = openssl_x509_read($issuerPem);

        if ($certificate === false || $issuer === false) {
            return -1;
        }

        return openssl_x509_verify($certificate, $issuer);
    }

    /**
     * @param array<string> $chain
     * @return array<string>
     */
    private function validateCaCertificates(array $chain): array
    {
        $messages = [];

        foreach (array_slice($chain, 1) as $index => $certificatePem) {
            $parsed = openssl_x509_parse($certificatePem);
            $label = $index === count($chain) - 2 ? 'trust anchor' : 'CA intermediaria';

            if (! is_array($parsed)) {
                $messages[] = "Nao foi possivel parsear {$label} da cadeia.";
                continue;
            }

            $extensions = $parsed['extensions'] ?? [];
            $basicConstraints = (string) ($extensions['basicConstraints'] ?? '');
            $keyUsage = (string) ($extensions['keyUsage'] ?? '');

            if (! str_contains(strtoupper($basicConstraints), 'CA:TRUE')) {
                $messages[] = "{$label} da cadeia nao possui Basic Constraints CA:TRUE.";
            }

            if ($keyUsage !== '' && ! str_contains(strtolower($keyUsage), 'certificate sign')) {
                $messages[] = "{$label} da cadeia nao permite keyCertSign.";
            }
        }

        return $messages;
    }

    /**
     * @param array<string> $chain
     * @return array<string, mixed>
     */
    private function chainDetails(array $chain): array
    {
        $normalizer = $this->normalizer ?? new CertificateFormatNormalizer();

        return [
            'chain_length' => count($chain),
            'fingerprints' => array_map(
                fn (string $certificatePem): string => strtoupper(hash('sha256', $normalizer->normalizeToDer($certificatePem))),
                $chain
            ),
            'subjects' => array_map(
                static function (string $certificatePem): array {
                    $parsed = openssl_x509_parse($certificatePem);

                    return is_array($parsed) ? ($parsed['subject'] ?? []) : [];
                },
                $chain
            ),
        ];
    }
}
