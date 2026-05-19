<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspEmbeddedResponseAttribute;
use PHPUnit\Framework\TestCase;

final class OcspEmbeddedResponseAttributeTest extends TestCase
{
    public function test_it_builds_ocsp_embedded_response_attribute(): void
    {
        $response = 'ocsp-response';

        $attribute = (new OcspEmbeddedResponseAttribute())
            ->build($response);

        $hex = strtoupper(
            bin2hex($attribute)
        );

        $this->assertStringContainsString(
            '2A864886F70D0109100204',
            $hex
        );

        $this->assertStringContainsString(
            strtoupper(
                bin2hex($response)
            ),
            $hex
        );
    }
}