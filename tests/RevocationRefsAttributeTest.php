<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Internal\Crypto\Cades\RevocationRefsAttribute;
use PHPUnit\Framework\TestCase;

final class RevocationRefsAttributeTest extends TestCase
{
    public function test_it_builds_revocation_refs_attribute(): void
    {
        $attribute = (new RevocationRefsAttribute())
            ->build(
                ocspResponsesDer: ['ocsp-response'],
                crlsDer: ['crl-content']
            );

        $this->assertNotEmpty($attribute);

        $this->assertStringContainsString(
            hex2bin('2a864886f70d0109100216'),
            $attribute
        );

        $this->assertStringContainsString(
            hash('sha256', 'ocsp-response', binary: true),
            $attribute
        );

        $this->assertStringContainsString(
            hash('sha256', 'crl-content', binary: true),
            $attribute
        );
    }
}