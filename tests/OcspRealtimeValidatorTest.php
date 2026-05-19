<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspRealtimeValidator;
use PHPUnit\Framework\TestCase;

final class OcspRealtimeValidatorTest extends TestCase
{
    public function test_it_returns_failed_result_when_ocsp_url_is_missing(): void
    {
        $result = (new OcspRealtimeValidator())
            ->validate(
                certificatePem: 'certificate',
                issuerNameDer: 'issuer-name',
                issuerPublicKeyDer: 'issuer-key',
                serialNumberHex: '01'
            );

        $this->assertFalse(
            $result->successful
        );
    }

    public function test_it_handles_invalid_ocsp_response(): void
    {
        $result = (new OcspRealtimeValidator())
            ->validate(
                certificatePem: <<<PEM
-----BEGIN CERTIFICATE-----
INVALID
-----END CERTIFICATE-----
PEM,
                issuerNameDer: 'issuer-name',
                issuerPublicKeyDer: 'issuer-key',
                serialNumberHex: '01'
            );

        $this->assertFalse(
            $result->successful
        );
    }
}