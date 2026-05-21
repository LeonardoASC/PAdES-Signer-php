<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use InvalidArgumentException;
use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use PHPUnit\Framework\TestCase;

final class SignatureAlgorithmPolicyTest extends TestCase
{
    public function test_it_builds_sha384_rsa_algorithm_identifiers(): void
    {
        $policy = new SignatureAlgorithmPolicy(
            hashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA384
        );

        $this->assertStringContainsString(
            hex2bin('608648016503040202'),
            $policy->digestAlgorithmIdentifier()
        );

        $this->assertStringContainsString(
            hex2bin('2a864886f70d01010c'),
            $policy->signatureAlgorithmIdentifier()
        );
    }

    public function test_it_builds_sha512_ecdsa_algorithm_identifier(): void
    {
        $policy = new SignatureAlgorithmPolicy(
            hashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA512,
            signatureAlgorithm: SignatureAlgorithmPolicy::SIGNATURE_ECDSA
        );

        $this->assertStringContainsString(
            hex2bin('608648016503040203'),
            $policy->digestAlgorithmIdentifier()
        );

        $this->assertStringContainsString(
            hex2bin('2a8648ce3d040304'),
            $policy->signatureAlgorithmIdentifier()
        );
    }

    public function test_it_builds_rsa_pss_algorithm_identifier(): void
    {
        $policy = new SignatureAlgorithmPolicy(
            signatureAlgorithm: SignatureAlgorithmPolicy::SIGNATURE_RSA_PSS
        );

        $identifier = $policy->signatureAlgorithmIdentifier();

        $this->assertStringContainsString(hex2bin('2a864886f70d01010a'), $identifier);
        $this->assertStringContainsString(hex2bin('2a864886f70d010108'), $identifier);
        $this->assertStringContainsString(hex2bin('608648016503040201'), $identifier);
    }

    public function test_it_rejects_hash_weaker_than_minimum(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SignatureAlgorithmPolicy(
            hashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA256,
            minimumHashAlgorithm: SignatureAlgorithmPolicy::HASH_SHA384
        );
    }

    public function test_it_rejects_unsupported_hash_algorithm(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SignatureAlgorithmPolicy(hashAlgorithm: 'sha1');
    }
}
