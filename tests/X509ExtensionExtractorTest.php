<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\X509\X509ExtensionExtractor;
use PHPUnit\Framework\TestCase;

final class X509ExtensionExtractorTest extends TestCase
{
    public function test_it_extracts_extensions(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $extractor = new X509ExtensionExtractor();

        $extensions = $extractor->extract(
            $certificate->getPublicCertificate()
        );

        $this->assertIsArray($extensions);
    }
}