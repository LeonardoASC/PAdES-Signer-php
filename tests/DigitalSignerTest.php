<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\DigitalSigner;
use PHPUnit\Framework\TestCase;

final class DigitalSignerTest extends TestCase
{
    public function test_it_signs_and_verifies_raw_data(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $signer = new DigitalSigner($certificate);

        $data = 'PAdES Core test data';

        $signature = $signer->sign($data);

        $this->assertNotEmpty($signature);
        $this->assertTrue(
            $signer->verify($data, $signature)
        );
    }
}