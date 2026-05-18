<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\AdvancedCmsSigner;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class AdvancedCmsSignerTest extends TestCase
{
    public function test_it_is_not_implemented_yet(): void
    {
        $this->expectException(RuntimeException::class);

        (new AdvancedCmsSigner())->signDetachedDer(
            'test',
            'cert.pfx',
            '123'
        );
    }
}