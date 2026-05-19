<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\X509\CertificateDownloader;
use PHPUnit\Framework\TestCase;

final class CertificateDownloaderTest extends TestCase
{
    public function test_it_returns_null_for_invalid_url(): void
    {
        $result = (new CertificateDownloader())
            ->download(
                'http://invalid.localhost/certificate.crt'
            );

        $this->assertNull($result);
    }
}