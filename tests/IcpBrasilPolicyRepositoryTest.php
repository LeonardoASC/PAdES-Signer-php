<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use DateTimeImmutable;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\IcpBrasil\IcpBrasilPolicyCache;
use NihilLabs\Pades\Crypto\IcpBrasil\IcpBrasilPolicyRepository;
use NihilLabs\Pades\Crypto\IcpBrasil\LpaDownloader;
use NihilLabs\Pades\Crypto\IcpBrasil\LpaParser;
use NihilLabs\Pades\Crypto\IcpBrasil\PolicyArtifactDownloader;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class IcpBrasilPolicyRepositoryTest extends TestCase
{
    public function test_it_resolves_ad_rt_pdf_policy_from_lpa_and_downloaded_pa(): void
    {
        $policyUri = 'http://politicas.icpbrasil.gov.br/PA_PAdES_AD_RT_v1_3.der';
        $policyBytes = 'real ad-rt policy artifact bytes';
        $policyHash = hash('sha256', $policyBytes, binary: true);

        $repository = new IcpBrasilPolicyRepository(
            lpaDownloader: new class($this->lpa($policyUri, $policyHash)) extends LpaDownloader {
                public function __construct(private readonly string $bytes) {}

                public function download(string $url): string
                {
                    return $this->bytes;
                }
            },
            artifactDownloader: new class([$policyUri => $policyBytes]) extends PolicyArtifactDownloader {
                /**
                 * @param array<string, string> $artifacts
                 */
                public function __construct(private readonly array $artifacts) {}

                public function downloadArtifact(string $url): string
                {
                    return $this->artifacts[$url]
                        ?? throw new RuntimeException('Unexpected policy URI.');
                }
            },
            cache: new IcpBrasilPolicyCache(
                directory: sys_get_temp_dir() . '/pades-core-test-' . bin2hex(random_bytes(4)),
                ttlSeconds: 60
            ),
            lpaUri: 'https://example.test/LPA_PAdES.der'
        );

        $resolved = $repository->resolveAdRtPdfPolicy();

        $this->assertSame('2.16.76.1.7.1.12.1.3', $resolved->policyOid);
        $this->assertSame($policyUri, $resolved->policyUri);
        $this->assertSame($policyHash, $resolved->policyHash);
        $this->assertSame('1.3', $resolved->version);
        $this->assertSame('AD-RT', $resolved->signatureType);
        $this->assertTrue($resolved->artifacts['policyHashMatchesLpa']);
    }

    public function test_it_downloads_official_iti_pades_lpa_when_network_is_available(): void
    {
        try {
            $lpaBytes = (new LpaDownloader(timeoutSeconds: 10, retries: 0))
                ->downloadPadesLpa();

            $parser = new LpaParser();
            $policies = $parser->parse($lpaBytes);
        } catch (Throwable $exception) {
            $this->markTestSkipped(
                'LPA PAdES ITI indisponivel: ' . $exception->getMessage()
            );
        }

        $policy = $parser->selectLatestCurrentPdfPolicy(
            policies: $policies,
            signatureType: 'AD-RT',
            at: new DateTimeImmutable('2026-05-19T00:00:00+00:00')
        );

        $this->writeJson(
            'lpa-debug.json',
            [
                'policies' => array_map(
                    static fn ($policy): array => $policy->toArray(),
                    $policies
                ),
            ]
        );

        $this->assertNotNull($policy);
        $this->assertSame('AD-RT', $policy->signatureType);
        $this->assertSame('PAdES', $policy->format);
        $this->assertStringContainsString('PA_PAdES_AD_RT', $policy->policyUri);
        $this->assertSame(32, strlen($policy->policyHash));
    }

    public function test_it_resolves_official_iti_ad_rt_and_ad_rc_policy_artifacts_when_network_is_available(): void
    {
        try {
            $repository = new IcpBrasilPolicyRepository(
                cache: new IcpBrasilPolicyCache(
                    directory: sys_get_temp_dir() . '/pades-core-real-policy-test-' . bin2hex(random_bytes(4)),
                    ttlSeconds: 60
                )
            );

            $adRt = $repository->resolveAdRtPdfPolicy();
            $adRc = $repository->resolveAdRcPdfPolicy();
        } catch (Throwable $exception) {
            $this->markTestSkipped(
                'Politicas PAdES ITI indisponiveis: ' . $exception->getMessage()
            );
        }

        $this->writeJson(
            'policy-debug.json',
            [
                'AD-RT' => $adRt->toArray(),
                'AD-RC' => $adRc->toArray(),
            ]
        );

        $this->assertSame('AD-RT', $adRt->signatureType);
        $this->assertSame('PAdES', $adRt->format);
        $this->assertStringContainsString('PA_PAdES_AD_RT', $adRt->policyUri);
        $this->assertSame(32, strlen($adRt->policyHash));
        $this->assertTrue($adRt->artifacts['policyHashMatchesLpa']);

        $this->assertSame('AD-RC', $adRc->signatureType);
        $this->assertSame('PAdES', $adRc->format);
        $this->assertStringContainsString('PA_PAdES_AD_RC', $adRc->policyUri);
        $this->assertSame(32, strlen($adRc->policyHash));
        $this->assertTrue($adRc->artifacts['policyHashMatchesLpa']);
    }

    private function lpa(string $policyUri, string $policyHash): string
    {
        return Der::sequence(
            Der::sequence(
                Der::sequence(
                    Der::sequence(
                        Der::generalizedTime('20250723000000Z')
                        . Der::generalizedTime('20371022000000Z')
                    )
                    . Der::oid('604C0107010C0103')
                    . Der::ia5String($policyUri)
                    . Der::sequence(
                        Der::sha256AlgorithmIdentifier()
                        . Der::octetString($policyHash)
                    )
                )
            )
            . Der::generalizedTime('20260705000000Z')
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeJson(string $file, array $payload): void
    {
        $directory = __DIR__ . '/Output';

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents(
            $directory . '/' . $file,
            json_encode(
                $payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            )
        );
    }
}
