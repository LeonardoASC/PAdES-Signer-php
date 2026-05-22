<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Crypto\X509\CallbackRevocationProvider;
use NihilLabs\Pades\Crypto\X509\RevocationMaterial;
use NihilLabs\Pades\Pdf\PdfAutomaticLtvEnricher;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Validation\PadesLtValidator;
use PHPUnit\Framework\TestCase;

final class PdfAutomaticLtvEnricherTest extends TestCase
{
    public function test_it_automatically_embeds_ltv_evidence_from_revocation_provider(): void
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: '123456'
        );
        $signedPdf = $this->timestampedPdf();

        $enrichedPdf = (new PdfAutomaticLtvEnricher())->enrich(
            signedPdfContent: $signedPdf,
            certificateChainPem: $certificate->getCertificateChain(),
            revocationProvider: new CallbackRevocationProvider(
                fn (array $chain): RevocationMaterial => new RevocationMaterial(
                    ocspResponsesDer: ["\x30\x03\x0A\x01\x00"],
                    crlsDer: ["\x30\x03\x0A\x01\x01"]
                )
            )
        );

        $result = (new PadesLtValidator())->validatePdf($enrichedPdf);

        $this->assertTrue($result->valid, implode("\n", $result->messages));
    }

    private function timestampedPdf(): string
    {
        $response = file_get_contents(__DIR__ . '/Output/timestamp-response.tsr');
        $this->assertNotFalse($response);

        $client = new class($response) implements TimestampClientInterface {
            public function __construct(private readonly string $response) {}

            public function requestToken(string $timestampRequestDer): string
            {
                return $this->response;
            }
        };

        $output = tempnam(sys_get_temp_dir(), 'pades-auto-lt-');
        $this->assertIsString($output);

        (new RealPdfSigner())->sign(
            inputPdf: __DIR__ . '/Fixtures/sample.pdf',
            outputPdf: $output,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: '123456',
            timestampClient: $client
        );

        $pdf = file_get_contents($output);
        $this->assertNotFalse($pdf);

        return $pdf;
    }
}
