<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Cades\IcpBrasilSignaturePolicy;
use NihilLabs\Pades\Crypto\Cades\SignaturePolicyIdentifierAttribute;
use PHPUnit\Framework\TestCase;

final class SignaturePolicyIdentifierAttributeTest extends TestCase
{
    public function test_it_builds_signature_policy_identifier_attribute(): void
    {
        $policyHash = str_repeat("\xAB", 32);

        $attribute = (new SignaturePolicyIdentifierAttribute())
            ->build(
                new IcpBrasilSignaturePolicy(
                    policyOid: '1.2.3.4',
                    policyHash: $policyHash
                )
            );

        $hex = strtoupper(bin2hex($attribute));

        $this->assertStringContainsString('2A864886F70D010910020F', $hex);
        $this->assertStringContainsString('06032A0304', $hex);
        $this->assertStringContainsString(strtoupper(bin2hex($policyHash)), $hex);
        $this->assertStringContainsString('608648016503040201', $hex);
    }

    public function test_it_includes_spuri_qualifier_when_uri_is_provided(): void
    {
        $uri = 'https://example.test/policy.der';

        $attribute = (new SignaturePolicyIdentifierAttribute())
            ->build(
                new IcpBrasilSignaturePolicy(
                    policyOid: '1.2.3.4',
                    policyHash: str_repeat("\xCD", 32),
                    policyUri: $uri
                )
            );

        $hex = strtoupper(bin2hex($attribute));

        $this->assertStringContainsString('2A864886F70D0109100501', $hex);
        $this->assertStringContainsString(strtoupper(bin2hex($uri)), $hex);
    }
}
