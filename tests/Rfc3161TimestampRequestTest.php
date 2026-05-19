<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Timestamp\Rfc3161TimestampRequest;
use PHPUnit\Framework\TestCase;

final class Rfc3161TimestampRequestTest extends TestCase
{
    public function test_it_builds_rfc3161_timestamp_request(): void
    {
        $request = (new Rfc3161TimestampRequest())
            ->build('signature-bytes');

        $this->assertNotEmpty($request);

        $this->assertStringStartsWith(
            "\x30",
            $request
        );

        $this->assertStringContainsString(
            hash('sha256', 'signature-bytes', binary: true),
            $request
        );
    }
}