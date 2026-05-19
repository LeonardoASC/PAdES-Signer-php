<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Asn1\Der;
use PHPUnit\Framework\TestCase;

final class DerContextSpecificImplicitFromEncodedTest extends TestCase
{
    public function test_it_replaces_original_tag_with_context_specific_tag(): void
    {
        $set = Der::set("\x01\x02");

        $encoded = Der::contextSpecificImplicitFromEncoded(
            tag: 0,
            encoded: $set
        );

        $this->assertSame(
            "\xA0\x02\x01\x02",
            $encoded
        );
    }
}