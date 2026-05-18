<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use TCPDF;

final class MinimalPdfGenerator
{
    public function generate(string $outputPath): void
    {
        $pdf = new TCPDF();

        $pdf->AddPage();

        $pdf->SetFont('helvetica', '', 12);

        $pdf->Write(
            0,
            'PAdES Core Test Document'
        );

        $pdf->Output(
            $outputPath,
            'F'
        );
    }
}