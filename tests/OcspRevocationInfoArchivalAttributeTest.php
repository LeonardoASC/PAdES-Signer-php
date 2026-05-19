<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspRevocationInfoArchivalAttribute;
use PHPUnit\Framework\TestCase;

final class OcspRevocationInfoArchivalAttributeTest extends TestCase
{
    public function test_it_builds_revocation_info_archival_attribute(): void
    {
        $attribute = (
            new OcspRevocationInfoArchivalAttribute()
        )->build([
            'ocsp-response-1',
            'ocsp-response-2',
        ]);

        $hex = strtoupper(
            bin2hex($attribute)
        );

        $this->assertStringContainsString(
            '2A864886F70D0109100224',
            $hex
        );

        $this->assertStringContainsString(
            strtoupper(
                bin2hex('ocsp-response-1')
            ),
            $hex
        );

        $this->assertStringContainsString(
            strtoupper(
                bin2hex('ocsp-response-2')
            ),
            $hex
        );
    }
}