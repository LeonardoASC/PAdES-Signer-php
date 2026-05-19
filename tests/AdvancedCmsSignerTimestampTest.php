<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use PHPUnit\Framework\TestCase;

final class AdvancedCmsSignerTimestampTest extends TestCase
{
    public function test_it_builds_cms_with_timestamp_attribute(): void
    {
        $response = file_get_contents(
            __DIR__ . '/Output/timestamp-response.tsr'
        );

        $this->assertNotFalse($response);

        $client = new class($response) implements TimestampClientInterface {
            public function __construct(
                private readonly string $response
            ) {}

            public function requestToken(
                string $timestampRequestDer
            ): string {
                return $this->response;
            }
        };

        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        $cms = (new AdvancedCmsSigner(
            certificate: $certificate,
            timestampClient: $client
        ))->signDetachedDer('hello world');

        $this->assertStringContainsString(
            hex2bin('2a864886f70d010910020e'),
            $cms
        );
    }
}