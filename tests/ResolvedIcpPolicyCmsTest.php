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
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Throwable;

final class ResolvedIcpPolicyCmsTest extends TestCase
{
    public function test_it_generates_cms_with_resolved_icp_policy_and_keeps_openssl_verify_successful(): void
    {
        $data = 'hello world';
        $policyHash = str_repeat("\x5A", 32);
        $resolvedPolicy = new ResolvedIcpPolicy(
            policyOid: '2.16.76.1.7.1.12.1.3',
            policyHash: $policyHash,
            policyUri: 'http://politicas.icpbrasil.gov.br/PA_PAdES_AD_RT_v1_3.der',
            signatureType: 'AD-RT',
            format: 'PAdES',
            version: '1.3'
        );

        $cms = (new AdvancedCmsSigner(
            certificate: $this->certificate(),
            signaturePolicy: IcpBrasilSignaturePolicy::fromResolvedPolicy($resolvedPolicy)
        ))->signDetachedDer($data);

        $this->assertStringContainsString(
            hex2bin('2A864886F70D010910020F'),
            $cms
        );

        $this->assertStringContainsString(
            Der::oid('604C0107010C0103'),
            $cms
        );

        $this->assertStringContainsString($policyHash, $cms);

        $this->assertTrue(
            (new OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $cms,
                    signedData: $data
                )
        );
    }

    public function test_it_generates_cms_with_real_resolved_iti_policy_when_network_is_available(): void
    {
        try {
            $resolvedPolicy = (new IcpBrasilPolicyRepository(
                cache: new IcpBrasilPolicyCache(
                    directory: __DIR__ . '/Output/icp-policy-cache',
                    ttlSeconds: 86400
                )
            ))->resolveAdRtPdfPolicy();
        } catch (Throwable $exception) {
            $this->markTestSkipped(
                'Politica AD-RT PAdES ITI indisponivel: ' . $exception->getMessage()
            );
        }

        $data = 'hello world';
        $cms = (new AdvancedCmsSigner(
            certificate: $this->certificate(),
            signaturePolicy: IcpBrasilSignaturePolicy::fromResolvedPolicy($resolvedPolicy)
        ))->signDetachedDer($data);

        $this->assertStringContainsString(
            Der::oid($this->dottedOidToHex($resolvedPolicy->policyOid)),
            $cms
        );

        $this->assertStringContainsString($resolvedPolicy->policyHash, $cms);

        $this->assertTrue(
            (new OpenSslBinaryCmsVerifier())
                ->verify(
                    cmsDer: $cms,
                    signedData: $data
                )
        );

        $this->writeCmsAsn1Dump(
            cmsDer: $cms,
            asn1Path: __DIR__ . '/Output/icp-policy-cms.asn1.txt'
        );
    }

    private function certificate(): PfxCertificate
    {
        return new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );
    }

    private function writeCmsAsn1Dump(
        string $cmsDer,
        string $asn1Path
    ): void {
        $directory = dirname($asn1Path);

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $cmsPath = tempnam(sys_get_temp_dir(), 'pades-policy-cms-');

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
}
