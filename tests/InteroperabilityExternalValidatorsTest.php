<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\Certificate\PfxCertificate;
use NihilLabs\Pades\Crypto\Timestamp\HttpTimestampClient;
use NihilLabs\Pades\Crypto\Timestamp\TimestampClientInterface;
use NihilLabs\Pades\Crypto\Validation\LtvValidationMaterial;
use NihilLabs\Pades\Crypto\Validation\RealLtvValidationMaterialFactory;
use NihilLabs\Pades\Pdf\MinimalPdfGenerator;
use NihilLabs\Pades\Pdf\PdfLtaEnricher;
use NihilLabs\Pades\Pdf\PdfLtvEnricher;
use NihilLabs\Pades\Pdf\RealPdfSigner;
use NihilLabs\Pades\Validation\PadesBbValidator;
use NihilLabs\Pades\Validation\PadesBtValidator;
use NihilLabs\Pades\Validation\PadesLtValidator;
use NihilLabs\Pades\Validation\PadesLtaValidator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class InteroperabilityExternalValidatorsTest extends TestCase
{
    public function test_it_generates_pdf_fixtures_for_external_interoperability_validators(): void
    {
        $fixtures = $this->fixtureSet();

        $this->assertFileExists($fixtures['b_b']);
        $this->assertFileExists($fixtures['b_t']);
        $this->assertFileExists($fixtures['b_lt']);
        $this->assertFileExists($fixtures['b_lta']);

        $bb = (new PadesBbValidator())->validatePdf($this->read($fixtures['b_b']));
        $bt = (new PadesBtValidator())->validatePdf($this->read($fixtures['b_t']));
        $lt = (new PadesLtValidator())->validatePdf($this->read($fixtures['b_lt']));
        $lta = (new PadesLtaValidator())->validatePdf($this->read($fixtures['b_lta']));

        $this->assertTrue($bb->valid, implode("\n", $bb->messages));
        $this->assertTrue($bt->valid, implode("\n", $bt->messages));
        $this->assertTrue($lt->valid, implode("\n", $lt->messages));
        $this->assertTrue($lta->valid, implode("\n", $lta->messages));
    }

    public function test_pyhanko_validates_php_generated_pdf_when_command_is_configured(): void
    {
        $commandTemplate = getenv('PYHANKO_VALIDATE_COMMAND');

        if (! is_string($commandTemplate) || $commandTemplate === '') {
            $this->markTestSkipped(
                'Configure PYHANKO_VALIDATE_COMMAND com {pdf}, por exemplo: pyhanko sign validate --pretty-print {pdf}'
            );
        }

        $fixtures = $this->fixtureSet();
        $output = $this->runExternalValidator($commandTemplate, $fixtures['b_t']);

        $this->assertNotSame('', trim($output));
    }

    public function test_php_validates_pyhanko_generated_pdf_when_fixture_is_available(): void
    {
        $fixture = getenv('PYHANKO_SIGNED_PDF');

        if (! is_string($fixture) || $fixture === '') {
            $fixture = __DIR__ . '/Fixtures/pyhanko-valid.pdf';
        }

        if (! is_file($fixture)) {
            $this->markTestSkipped('Configure PYHANKO_SIGNED_PDF ou adicione tests/Fixtures/pyhanko-valid.pdf.');
        }

        $result = (new PadesBbValidator())->validatePdf($this->read($fixture));

        $this->assertTrue($result->checks['byte_range_valid'] ?? false, implode("\n", $result->messages));
        $this->assertTrue($result->checks['cms_message_digest'] ?? false, implode("\n", $result->messages));
    }

    public function test_dss_european_validator_accepts_generated_pdf_when_command_is_configured(): void
    {
        $commandTemplate = getenv('DSS_VALIDATE_COMMAND');

        if (! is_string($commandTemplate) || $commandTemplate === '') {
            $this->markTestSkipped('Configure DSS_VALIDATE_COMMAND com {pdf} apontando para o validador DSS local.');
        }

        $fixtures = $this->fixtureSet();
        $output = $this->runExternalValidator($commandTemplate, $fixtures['b_lt']);

        $this->assertNotSame('', trim($output));
    }

    public function test_etsi_validator_accepts_generated_pdf_when_command_is_configured(): void
    {
        $commandTemplate = getenv('ETSI_VALIDATE_COMMAND');

        if (! is_string($commandTemplate) || $commandTemplate === '') {
            $this->markTestSkipped('Configure ETSI_VALIDATE_COMMAND com {pdf} apontando para o validador ETSI local.');
        }

        $fixtures = $this->fixtureSet();
        $output = $this->runExternalValidator($commandTemplate, $fixtures['b_lta']);

        $this->assertNotSame('', trim($output));
    }

    public function test_international_interoperability_fixture_can_be_validated_by_php(): void
    {
        $fixture = getenv('INTERNATIONAL_PADES_FIXTURE');

        if (! is_string($fixture) || $fixture === '' || ! is_file($fixture)) {
            $this->markTestSkipped('Configure INTERNATIONAL_PADES_FIXTURE com um PDF PAdES internacional.');
        }

        $result = (new PadesBbValidator())->validatePdf($this->read($fixture));

        $this->assertTrue($result->checks['byte_range_valid'] ?? false, implode("\n", $result->messages));
        $this->assertTrue($result->checks['cms_message_digest'] ?? false, implode("\n", $result->messages));
    }

    /**
     * @return array{b_b:string,b_t:string,b_lt:string,b_lta:string}
     */
    private function fixtureSet(): array
    {
        $directory = __DIR__ . '/Output/interoperability';

        if (! is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $input = $directory . '/input.pdf';
        $bb = $directory . '/php-generated-b-b.pdf';
        $bt = $directory . '/php-generated-b-t.pdf';
        $lt = $directory . '/php-generated-b-lt.pdf';
        $lta = $directory . '/php-generated-b-lta.pdf';

        (new MinimalPdfGenerator())->generate($input);

        (new RealPdfSigner())->sign(
                inputPdf: $input,
                outputPdf: $bb,
                certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
                certificatePassword: $this->certificatePassword()
            );

        (new RealPdfSigner())->sign(
            inputPdf: $input,
            outputPdf: $bt,
            certificatePath: __DIR__ . '/Fixtures/certificate.pfx',
            certificatePassword: $this->certificatePassword(),
                timestampClient: $this->timestampClient()
            );

        file_put_contents(
            $lt,
            $this->ltPdfContent($bt)
        );

        file_put_contents(
            $lta,
            (new PdfLtaEnricher())->addArchiveTimestamp(
                ltPdfContent: $this->read($lt),
                timestampProvider: $this->timestampClient(),
                fieldName: 'ArchiveTimeStamp1'
            )
        );

        return [
            'b_b' => $bb,
            'b_t' => $bt,
            'b_lt' => $lt,
            'b_lta' => $lta,
        ];
    }

    private function ltvMaterial(): LtvValidationMaterial
    {
        $certificate = new PfxCertificate(
            path: __DIR__ . '/Fixtures/certificate.pfx',
            password: $this->certificatePassword()
        );

        return (new RealLtvValidationMaterialFactory())->create(
            signerCertificatePem: $certificate->getPublicCertificate(),
            candidateCertificatesPem: $certificate->getExtraCertificates()
        );
    }

    private function ltPdfContent(string $bt): string
    {
        try {
            return (new PdfLtvEnricher())->enrich(
                signedPdfContent: $this->read($bt),
                material: $this->ltvMaterial()
            );
        } catch (RuntimeException $exception) {
            $this->markTestSkipped(
                'Nao foi possivel coletar evidencias reais de LTV para fixture externa: '
                . $exception->getMessage()
            );
        }
    }

    private function timestampClient(): TimestampClientInterface
    {
        $tsaUrl = getenv('PADES_INTEROP_TSA_URL');

        if (! is_string($tsaUrl) || $tsaUrl === '') {
            $this->markTestSkipped('Configure PADES_INTEROP_TSA_URL para gerar fixtures B-T/B-LT/B-LTA externas com timestamp real.');
        }

        return new HttpTimestampClient($tsaUrl);
    }

    private function certificatePassword(): string
    {
        $password = getenv('PADES_INTEROP_PFX_PASSWORD');

        if (! is_string($password) || $password === '') {
            $this->markTestSkipped('Configure PADES_INTEROP_PFX_PASSWORD com a senha local do certificate.pfx.');
        }

        return $password;
    }

    private function runExternalValidator(string $commandTemplate, string $pdf): string
    {
        $command = str_replace('{pdf}', escapeshellarg($pdf), $commandTemplate);

        exec($command . ' 2>&1', $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(implode(PHP_EOL, $output));
        }

        return implode(PHP_EOL, $output);
    }

    private function read(string $path): string
    {
        $content = file_get_contents($path);

        if ($content === false) {
            throw new RuntimeException("Nao foi possivel ler {$path}.");
        }

        return $content;
    }
}
