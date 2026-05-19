<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\Dss\PdfVriDictionary;
use PHPUnit\Framework\TestCase;

final class PdfVriDictionaryTest extends TestCase
{
    public function test_it_builds_a_vri_dictionary_for_a_signature_hash(): void
    {
        $dictionary = (new PdfVriDictionary())
            ->build(
                signatureHash: 'abc123',
                certReferences: ['10 0 R'],
                ocspReferences: ['11 0 R'],
                crlReferences: ['12 0 R']
            );

        $this->assertStringContainsString('/ABC123 <<', $dictionary);
        $this->assertStringContainsString('/Cert [10 0 R]', $dictionary);
        $this->assertStringContainsString('/OCSP [11 0 R]', $dictionary);
        $this->assertStringContainsString('/CRL [12 0 R]', $dictionary);
    }
}
