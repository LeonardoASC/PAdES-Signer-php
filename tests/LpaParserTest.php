<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use DateTimeImmutable;
use NihilLabs\Pades\Crypto\Asn1\Der;
use NihilLabs\Pades\Crypto\IcpBrasil\LpaParser;
use PHPUnit\Framework\TestCase;

final class LpaParserTest extends TestCase
{
    public function test_it_extracts_and_selects_latest_current_ad_rt_pades_policy_from_der_lpa(): void
    {
        $adRtHash = hash('sha256', 'ad-rt-v13-policy', binary: true);
        $revokedHash = hash('sha256', 'ad-rt-v12-policy', binary: true);

        $lpa = $this->lpa([
            $this->policyEntry(
                oidHex: '604C0107010C0102',
                uri: 'http://politicas.icpbrasil.gov.br/PA_PAdES_AD_RT_v1_2.der',
                hash: $revokedHash,
                validFrom: '20250612000000Z',
                validUntil: '20371022000000Z',
                revokedAt: '20250723000000Z'
            ),
            $this->policyEntry(
                oidHex: '604C0107010C0103',
                uri: 'http://politicas.icpbrasil.gov.br/PA_PAdES_AD_RT_v1_3.der',
                hash: $adRtHash,
                validFrom: '20250723000000Z',
                validUntil: '20371022000000Z'
            ),
        ]);

        $parser = new LpaParser();
        $policies = $parser->parse($lpa);
        $policy = $parser->selectLatestCurrentPdfPolicy(
            policies: $policies,
            signatureType: 'AD-RT',
            at: new DateTimeImmutable('2026-05-19T00:00:00+00:00')
        );

        $this->assertNotNull($policy);
        $this->assertSame('2.16.76.1.7.1.12.1.3', $policy->policyOid);
        $this->assertSame('1.3', $policy->version);
        $this->assertSame(bin2hex($adRtHash), $policy->policyHashHex());
        $this->assertSame('AD-RT', $policy->signatureType);
        $this->assertSame('PAdES', $policy->format);
    }

    public function test_it_extracts_pades_policy_from_xml_lpa(): void
    {
        $hashHex = hash('sha256', 'ad-rc-v14-policy');

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<LPA>
  <Policy>
    <ValidFrom>20250723000000Z</ValidFrom>
    <ValidUntil>20371022000000Z</ValidUntil>
    <PolicyOid>2.16.76.1.7.1.13.1.4</PolicyOid>
    <PolicyUri>http://politicas.icpbrasil.gov.br/PA_PAdES_AD_RC_v1_4.der</PolicyUri>
    <PolicyHash>{$hashHex}</PolicyHash>
  </Policy>
</LPA>
XML;

        $policies = (new LpaParser())->parse($xml);

        $this->assertCount(1, $policies);
        $this->assertSame('2.16.76.1.7.1.13.1.4', $policies[0]->policyOid);
        $this->assertSame('AD-RC', $policies[0]->signatureType);
        $this->assertSame('1.4', $policies[0]->version);
        $this->assertSame($hashHex, $policies[0]->policyHashHex());
    }

    public function test_it_extracts_lpa_embedded_in_signed_cms_container(): void
    {
        $hash = hash('sha256', 'ad-rt-v13-policy', binary: true);
        $lpa = $this->lpa([
            $this->policyEntry(
                oidHex: '604C0107010C0103',
                uri: 'http://politicas.icpbrasil.gov.br/PA_PAdES_AD_RT_v1_3.der',
                hash: $hash,
                validFrom: '20250723000000Z',
                validUntil: '20371022000000Z'
            ),
        ]);

        $cmsLikeContainer = Der::sequence(
            Der::oid('2A864886F70D010702')
            . Der::contextSpecificConstructed(
                0,
                Der::octetString($lpa)
            )
        );

        $policies = (new LpaParser())->parse($cmsLikeContainer);

        $this->assertCount(1, $policies);
        $this->assertSame('2.16.76.1.7.1.12.1.3', $policies[0]->policyOid);
        $this->assertSame(bin2hex($hash), $policies[0]->policyHashHex());
    }

    /**
     * @param array<int, string> $entries
     */
    private function lpa(array $entries): string
    {
        return Der::sequence(
            Der::sequence(implode('', $entries))
            . Der::generalizedTime('20260705000000Z')
        );
    }

    private function policyEntry(
        string $oidHex,
        string $uri,
        string $hash,
        string $validFrom,
        string $validUntil,
        ?string $revokedAt = null
    ): string {
        return Der::sequence(
            Der::sequence(
                Der::generalizedTime($validFrom)
                . Der::generalizedTime($validUntil)
            )
            . ($revokedAt !== null ? Der::generalizedTime($revokedAt) : '')
            . Der::oid($oidHex)
            . Der::ia5String($uri)
            . Der::sequence(
                Der::sha256AlgorithmIdentifier()
                . Der::octetString($hash)
            )
        );
    }
}
