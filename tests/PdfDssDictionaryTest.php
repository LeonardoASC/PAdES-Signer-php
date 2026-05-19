<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Pdf\Dss\PdfDssDictionary;
use PHPUnit\Framework\TestCase;

final class PdfDssDictionaryTest extends TestCase
{
    public function test_it_builds_a_dss_dictionary_with_validation_references(): void
    {
        $dictionary = (new PdfDssDictionary())
            ->build(
                certReferences: ['10 0 R'],
                ocspReferences: ['11 0 R'],
                crlReferences: ['12 0 R'],
                vriDictionary: "<<\n/ABC123 <<\n/Cert [10 0 R]\n/OCSP [11 0 R]\n/CRL [12 0 R]\n>>\n>>"
            );

        $this->assertStringContainsString('/Type /DSS', $dictionary);
        $this->assertStringContainsString('/Certs [10 0 R]', $dictionary);
        $this->assertStringContainsString('/OCSPs [11 0 R]', $dictionary);
        $this->assertStringContainsString('/CRLs [12 0 R]', $dictionary);
        $this->assertStringContainsString('/VRI <<', $dictionary);
    }
}
