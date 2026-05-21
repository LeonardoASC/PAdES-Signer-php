<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Internal\Crypto\Cades\CertificateRefsAttribute;
use PHPUnit\Framework\TestCase;

final class CertificateRefsAttributeTest extends TestCase
{
    public function test_it_builds_certificate_refs_attribute(): void
    {
        $attribute = (new CertificateRefsAttribute())
            ->build([
                'certificate-der',
            ]);

        $this->assertNotEmpty($attribute);

        $this->assertStringContainsString(
            hex2bin('2a864886f70d0109100215'),
            $attribute
        );

        $this->assertStringContainsString(
            hash('sha256', 'certificate-der', binary: true),
            $attribute
        );

        $this->assertStringContainsString(
            hex2bin('30333031300d060960864801650304020105000420'),
            $attribute
        );
    }
}
