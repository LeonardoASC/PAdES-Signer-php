<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Tests;

use NihilLabs\Pades\PdfSigner;
use PHPUnit\Framework\TestCase;

final class PdfSignerTest extends TestCase
{
    public function test_it_copies_a_pdf(): void
    {
        $signer = new PdfSigner();

        $input = __DIR__ . '/Fixtures/sample.pdf';
        $output = __DIR__ . '/Output/signed.pdf';

        if (! is_dir(dirname($output))) {
            mkdir(dirname($output), 0777, true);
        }

        $signer->sign($input, $output);

        $this->assertFileExists($output);
    }
}