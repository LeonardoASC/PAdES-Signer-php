<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\X509\RealCertificateChainCollector;
use NihilLabs\Pades\Tests\Support\RealIcpSignedPdfFixture;
use PHPUnit\Framework\TestCase;

final class RealCertificateChainCollectorTest extends TestCase
{
    public function test_it_collects_real_icp_chain_from_aia(): void
    {
        if (! RealIcpSignedPdfFixture::isAvailable()) {
            $this->markTestSkipped(RealIcpSignedPdfFixture::skipMessage());
        }

        $certificate = RealIcpSignedPdfFixture::certificate();

        $chain = (new RealCertificateChainCollector())
            ->collect(
                signerCertificatePem: $certificate->getPublicCertificate(),
                candidateCertificatesPem: $certificate->getCertificateChain()
            );

        $this->assertStringContainsString(
            '-----BEGIN CERTIFICATE-----',
            $chain['signer']
        );

        $this->assertNotEmpty($chain['intermediates']);
        $this->assertGreaterThanOrEqual(2, count($chain['chain']));

        foreach ($chain['chain'] as $certificatePem) {
            $this->assertStringContainsString(
                '-----BEGIN CERTIFICATE-----',
                $certificatePem
            );
        }
    }
}
