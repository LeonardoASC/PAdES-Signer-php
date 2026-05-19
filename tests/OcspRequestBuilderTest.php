<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspRequestBuilder;
use PHPUnit\Framework\TestCase;

final class OcspRequestBuilderTest extends TestCase
{
    public function test_it_builds_ocsp_request(): void
    {
        $request = (new OcspRequestBuilder())
            ->build(
                issuerNameDer: 'issuer-name',
                issuerPublicKeyDer: 'issuer-key',
                serialNumberHex: '01AB'
            );

        $this->assertNotEmpty($request);

        $hex = strtoupper(
            bin2hex($request)
        );

        $this->assertStringContainsString(
            '2B0E03021A',
            $hex
        );

        $this->assertStringContainsString(
            strtoupper(
                sha1('issuer-name')
            ),
            $hex
        );

        $this->assertStringContainsString(
            strtoupper(
                sha1('issuer-key')
            ),
            $hex
        );
    }
}