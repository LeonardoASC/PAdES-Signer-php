<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Pdf;

use NihilLabs\Pades\Exception\InvalidPadesArgumentException;
use NihilLabs\Pades\Exception\PdfReadException;
use NihilLabs\Pades\Exception\PdfSizeException;

final readonly class PdfFileGuard
{
    public function read(string $path, ?int $maxBytes = null): string
    {
        $this->assertReadable($path, $maxBytes);

        $content = file_get_contents($path);

        if ($content === false) {
            throw new PdfReadException("PDF nao encontrado ou ilegivel: {$path}");
        }

        return $content;
    }

    public function assertReadable(string $path, ?int $maxBytes = null): void
    {
        $this->assertMaxBytes($maxBytes);

        if (! is_file($path) || ! is_readable($path)) {
            throw new PdfReadException("PDF nao encontrado ou ilegivel: {$path}");
        }

        $size = filesize($path);

        if ($size === false) {
            throw new PdfReadException("Tamanho do PDF nao pode ser obtido: {$path}");
        }

        if ($maxBytes !== null && $size > $maxBytes) {
            throw new PdfSizeException(
                "PDF excede o tamanho maximo configurado de {$maxBytes} bytes: {$path}"
            );
        }
    }

    private function assertMaxBytes(?int $maxBytes): void
    {
        if ($maxBytes !== null && $maxBytes < 1) {
            throw new InvalidPadesArgumentException('maxInputPdfBytes deve ser maior que zero.');
        }
    }
}
