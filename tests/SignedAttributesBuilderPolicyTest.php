<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Asn1\DerReader;
use NihilLabs\Pades\Crypto\Cades\IcpBrasilSignaturePolicy;
use NihilLabs\Pades\Crypto\Cades\SignedAttributesBuilder;
use PHPUnit\Framework\TestCase;

final class SignedAttributesBuilderPolicyTest extends TestCase
{
    public function test_it_includes_signature_policy_when_configured(): void
    {
        $attributes = $this->buildAttributes(
            IcpBrasilSignaturePolicy::adRtPdfPlaceholder(
                policyHash: str_repeat("\x11", 32)
            )
        );

        $this->assertStringContainsString(
            hex2bin('2A864886F70D010910020F'),
            $attributes
        );
    }

    public function test_it_does_not_include_signature_policy_by_default(): void
    {
        $attributes = $this->buildAttributes();

        $this->assertStringNotContainsString(
            hex2bin('2A864886F70D010910020F'),
            $attributes
        );
    }

    public function test_it_keeps_signed_attributes_der_sorted(): void
    {
        $attributes = $this->buildAttributes(
            IcpBrasilSignaturePolicy::adRtPdfPlaceholder(
                policyHash: str_repeat("\x22", 32)
            )
        );

        $encodedAttributes = array_map(
            static fn (array $attribute): string => $attribute['encoded'],
            (new DerReader())->children($attributes)
        );

        $sorted = $encodedAttributes;
        usort(
            $sorted,
            static fn (string $left, string $right): int => strcmp($left, $right)
        );

        $this->assertSame($sorted, $encodedAttributes);
    }

    private function buildAttributes(
        ?IcpBrasilSignaturePolicy $policy = null
    ): string {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );

        return (new SignedAttributesBuilder())
            ->build(
                data: 'hello world',
                certificatePem: $certificate->getPublicCertificate(),
                signaturePolicy: $policy
            );
    }
}
