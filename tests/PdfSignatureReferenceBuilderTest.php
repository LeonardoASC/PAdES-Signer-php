<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use InvalidArgumentException;
use NihilLabs\Pades\Pdf\PdfSignatureReferenceBuilder;
use PHPUnit\Framework\TestCase;

final class PdfSignatureReferenceBuilderTest extends TestCase
{
    public function test_it_builds_doc_mdp_reference(): void
    {
        $reference = (new PdfSignatureReferenceBuilder())->build(
            catalogObjectNumber: 1,
            certificationPermission: 2
        );

        $this->assertStringContainsString('/Reference [', $reference);
        $this->assertStringContainsString('/TransformMethod /DocMDP', $reference);
        $this->assertStringContainsString('/TransformParams <<', $reference);
        $this->assertStringContainsString('/P 2', $reference);
        $this->assertStringContainsString('/V /1.2', $reference);
        $this->assertStringContainsString('/Data 1 0 R', $reference);
    }

    public function test_it_builds_field_mdp_reference_for_locked_fields(): void
    {
        $reference = (new PdfSignatureReferenceBuilder())->build(
            catalogObjectNumber: 1,
            lockedFieldNames: ['Approval', 'Amount'],
            fieldLockAction: 'Include'
        );

        $this->assertStringContainsString('/TransformMethod /FieldMDP', $reference);
        $this->assertStringContainsString('/Action /Include', $reference);
        $this->assertStringContainsString('/Fields [(Approval) (Amount)]', $reference);
    }

    public function test_it_rejects_invalid_doc_mdp_permission(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PdfSignatureReferenceBuilder())->build(
            catalogObjectNumber: 1,
            certificationPermission: 4
        );
    }
}
