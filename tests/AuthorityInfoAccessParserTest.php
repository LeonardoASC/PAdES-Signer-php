<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Crypto\X509\AuthorityInfoAccessParser;
use PHPUnit\Framework\TestCase;

final class AuthorityInfoAccessParserTest extends TestCase
{
    public function test_it_extracts_ocsp_and_ca_issuers_urls(): void
    {
        $value = <<<TEXT
OCSP - URI:http://ocsp.example.com
CA Issuers - URI:http://ca.example.com/issuer.crt
TEXT;

        $parser = new AuthorityInfoAccessParser();

        $this->assertSame(
            'http://ocsp.example.com',
            $parser->ocspUrl($value)
        );

        $this->assertSame(
            'http://ca.example.com/issuer.crt',
            $parser->caIssuersUrl($value)
        );
    }
}