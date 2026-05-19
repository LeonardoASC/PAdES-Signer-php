<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class RealOcspValidationReportExporter
{
    public function export(
        string $path,
        string $certificatePem,
        string $issuerCertificatePem
    ): void {
        $report = (
            new RealOcspValidationReport()
        )->generate(
            certificatePem: $certificatePem,
            issuerCertificatePem: $issuerCertificatePem
        );

        $json = (
            new RealOcspValidationReportJsonSerializer()
        )->serialize(
            $report
        );

        (
            new RealOcspValidationReportFileWriter()
        )->write(
            $path,
            $json
        );
    }
}