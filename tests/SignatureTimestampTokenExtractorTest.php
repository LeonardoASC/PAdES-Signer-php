<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampValidator;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Internal\Crypto\Cades\SignatureTimestampTokenExtractor;
use NihilLabs\Pades\Internal\Crypto\PadesCmsSigner;
use PHPUnit\Framework\TestCase;

final class SignatureTimestampTokenExtractorTest extends TestCase
{
    public function test_it_extracts_signature_timestamp_token_from_cms_unsigned_attributes(): void
    {
        $response = file_get_contents(__DIR__ . '/Output/timestamp-response.tsr');
        $this->assertNotFalse($response);

        $client = new class($response) implements TimestampClientInterface {
            public function __construct(private readonly string $response) {}

            public function requestToken(string $timestampRequestDer): string
            {
                return $this->response;
            }
        };

        $cms = (new PadesCmsSigner(
            certificate: new PfxCertificate(
                path: __DIR__ . '/Fixtures/certificate.pfx',
                password: '123456'
            ),
            timestampClient: $client
        ))->signPdfByteRangeData('hello b-t');

        $token = (new SignatureTimestampTokenExtractor())->extract($cms);
        $result = (new Rfc3161TimestampValidator())->validateToken($token);

        $this->assertTrue($result->valid, implode("\n", $result->messages));
    }
}
