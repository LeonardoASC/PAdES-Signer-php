<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\IcpBrasil\PolicyHashCalculator;
use PHPUnit\Framework\TestCase;

final class PolicyHashCalculatorTest extends TestCase
{
    public function test_it_calculates_sha256_from_policy_artifact_bytes(): void
    {
        $policyArtifact = '%PDF-1.7 policy artifact bytes';

        $calculator = new PolicyHashCalculator();

        $this->assertSame(
            hash('sha256', $policyArtifact),
            $calculator->sha256Hex($policyArtifact)
        );

        $this->assertSame(
            hash('sha256', $policyArtifact, binary: true),
            $calculator->sha256($policyArtifact)
        );
    }
}
