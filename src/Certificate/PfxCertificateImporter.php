<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Certificate;

final readonly class PfxCertificateImporter
{
    public function inspectContents(string $contents, string $password): PfxCertificateMetadata
    {
        return PfxCertificateMetadata::fromCertificate(
            PfxCertificate::fromContents(
                contents: $contents,
                password: $password
            )
        );
    }

    public function inspectFile(string $path, string $password): PfxCertificateMetadata
    {
        return PfxCertificateMetadata::fromCertificate(
            new PfxCertificate(
                path: $path,
                password: $password
            )
        );
    }
}
