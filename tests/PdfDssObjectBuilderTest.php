<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Pdf\Dss\PdfDssObjectBuilder;
use PHPUnit\Framework\TestCase;

final class PdfDssObjectBuilderTest extends TestCase
{
    public function test_it_builds_der_stream_objects_and_a_dss_object(): void
    {
        $result = (new PdfDssObjectBuilder())
            ->build(
                firstObjectNumber: 20,
                material: new LtvValidationMaterial(
                    certificatesDer: ["\x30\x01\x01"],
                    ocspResponsesDer: ["\x30\x01\x02"],
                    crlsDer: ["\x30\x01\x03"]
                ),
                vriHash: 'AABBCC'
            );

        $this->assertSame(23, $result['dssObjectNumber']);
        $this->assertSame([20], $result['certObjectNumbers']);
        $this->assertSame([21], $result['ocspObjectNumbers']);
        $this->assertSame([22], $result['crlObjectNumbers']);

        $this->assertStringContainsString("/Length 3\n", $result['objects'][20]);
        $this->assertStringContainsString("stream\n\x30\x01\x01\nendstream", $result['objects'][20]);
        $this->assertStringContainsString('/Certs [20 0 R]', $result['objects'][23]);
        $this->assertStringContainsString('/OCSPs [21 0 R]', $result['objects'][23]);
        $this->assertStringContainsString('/CRLs [22 0 R]', $result['objects'][23]);
        $this->assertStringContainsString('/AABBCC <<', $result['objects'][23]);
    }

    public function test_it_deduplicates_validation_material_objects(): void
    {
        $result = (new PdfDssObjectBuilder())
            ->build(
                firstObjectNumber: 20,
                material: new LtvValidationMaterial(
                    certificatesDer: ["\x30\x01\x01", "\x30\x01\x01"],
                    ocspResponsesDer: ["\x30\x01\x02", "\x30\x01\x02"],
                    crlsDer: ["\x30\x01\x03", "\x30\x01\x03"]
                ),
                vriHash: 'AABBCC'
            );

        $this->assertSame([20], $result['certObjectNumbers']);
        $this->assertSame([21], $result['ocspObjectNumbers']);
        $this->assertSame([22], $result['crlObjectNumbers']);
        $this->assertSame(23, $result['dssObjectNumber']);
    }
}
