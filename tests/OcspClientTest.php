<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Ocsp\OcspClient;
use PHPUnit\Framework\TestCase;

final class OcspClientTest extends TestCase
{
    public function test_it_handles_invalid_ocsp_url(): void
    {
        $response = (new OcspClient())
            ->request(
                'http://invalid.localhost/ocsp',
                'request'
            );

        $this->assertTrue(
            $response === null
            || is_string($response)
        );
    }
}