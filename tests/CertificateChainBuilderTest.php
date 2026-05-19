<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\X509\CertificateChainBuilder;
use PHPUnit\Framework\TestCase;

final class CertificateChainBuilderTest extends TestCase
{
    public function test_it_returns_original_certificate_when_no_aia_exists(): void
    {
        $chain = (new CertificateChainBuilder())
            ->build('certificate');

        $this->assertCount(
            1,
            $chain
        );

        $this->assertSame(
            'certificate',
            $chain[0]
        );
    }
}