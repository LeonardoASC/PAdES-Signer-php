<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\X509\AuthorityInfoAccessParser;
use NihilLabs\Pades\Crypto\X509\RealCertificateChainCollector;
use NihilLabs\Pades\Crypto\X509\X509ExtensionExtractor;
use RuntimeException;

final readonly class RealOcspMaterialCollector
{
    public function __construct(
        private ?RealCertificateChainCollector $chainCollector = null,
        private ?X509ExtensionExtractor $extensionExtractor = null,
        private ?AuthorityInfoAccessParser $aiaParser = null,
        private ?RealOcspRequestFactory $requestFactory = null,
        private ?OcspClient $client = null
    ) {}

    /**
     * @param array<string> $candidateCertificatesPem
     */
    public function collect(
        string $signerCertificatePem,
        array $candidateCertificatesPem = []
    ): RealOcspMaterial
    {
        $chainData = ($this->chainCollector ?? new RealCertificateChainCollector())
            ->collect(
                signerCertificatePem: $signerCertificatePem,
                candidateCertificatesPem: $candidateCertificatesPem
            );

        $chain = $chainData['chain'];
        $issuerCertificatePem = $chain[1] ?? null;

        if ($issuerCertificatePem === null) {
            throw new RuntimeException('Issuer do certificado nao foi encontrado via AIA.');
        }

        $ocspUrl = $this->ocspUrlFromCertificates([
            $chainData['signer'],
            ...$chain,
            ...$candidateCertificatesPem,
        ]);

        if ($ocspUrl === null) {
            throw new RuntimeException(
                'URL OCSP nao encontrada no AIA da cadeia de certificados. '
                . $this->aiaDiagnostic([
                    $chainData['signer'],
                    ...$chain,
                    ...$candidateCertificatesPem,
                ])
            );
        }

        $requestDer = ($this->requestFactory ?? new RealOcspRequestFactory())
            ->build(
                certificatePem: $chainData['signer'],
                issuerCertificatePem: $issuerCertificatePem
            );

        $responseDer = ($this->client ?? new OcspClient())
            ->request(
                url: $ocspUrl,
                requestDer: $requestDer
            );

        if ($responseDer === null) {
            throw new RuntimeException('Resposta OCSP nao recebida.');
        }

        $status = (new OcspResponseStatusInspector())
            ->inspect($responseDer);

        if ($status === null) {
            throw new RuntimeException('Resposta OCSP invalida ou sem certStatus.');
        }

        return new RealOcspMaterial(
            responseDer: $responseDer,
            status: $status,
            ocspUrl: $ocspUrl,
            issuerCertificatePem: $issuerCertificatePem,
            responderCertificatesDer: $this->uniqueDerObjects(
                (new OcspResponseCertificateExtractor())->extract($responseDer)
            ),
            certificateChainPem: $chain
        );
    }

    private function ocspUrl(string $certificatePem): ?string
    {
        $aia = ($this->extensionExtractor ?? new X509ExtensionExtractor())
            ->authorityInfoAccess($certificatePem);

        if ($aia === null) {
            return null;
        }

        return ($this->aiaParser ?? new AuthorityInfoAccessParser())
            ->ocspUrl($aia);
    }

    /**
     * @param array<string> $certificatesPem
     */
    private function ocspUrlFromCertificates(array $certificatesPem): ?string
    {
        $seen = [];

        foreach ($certificatesPem as $certificatePem) {
            $fingerprint = hash('sha256', $certificatePem);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $ocspUrl = $this->ocspUrl($certificatePem);

            if ($ocspUrl !== null) {
                return $ocspUrl;
            }
        }

        return null;
    }

    /**
     * @param array<string> $certificatesPem
     */
    private function aiaDiagnostic(array $certificatesPem): string
    {
        $lines = [];
        $seen = [];

        foreach ($certificatesPem as $index => $certificatePem) {
            $fingerprint = hash('sha256', $certificatePem);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $aia = ($this->extensionExtractor ?? new X509ExtensionExtractor())
                ->authorityInfoAccess($certificatePem);

            $subject = $this->certificateSubject($certificatePem);
            $urls = [];

            if ($aia !== null) {
                preg_match_all('/https?:\/\/[^\s,;<>"]+/i', $aia, $matches);
                $urls = $matches[0] ?? [];
            }

            $lines[] = sprintf(
                'cert#%d subject="%s" aia_urls=[%s]',
                $index,
                $subject,
                implode(', ', array_values(array_unique($urls)))
            );
        }

        return 'Diagnostico: ' . implode(' | ', $lines);
    }

    private function certificateSubject(string $certificatePem): string
    {
        $parsed = @openssl_x509_parse($certificatePem);

        if ($parsed === false) {
            return 'unavailable';
        }

        return $parsed['subject']['CN']
            ?? $parsed['name']
            ?? 'unavailable';
    }

    /**
     * @param array<string> $objects
     * @return array<string>
     */
    private function uniqueDerObjects(array $objects): array
    {
        $unique = [];
        $seen = [];

        foreach ($objects as $object) {
            $hash = hash('sha256', $object);

            if (isset($seen[$hash])) {
                continue;
            }

            $seen[$hash] = true;
            $unique[] = $object;
        }

        return $unique;
    }
}
