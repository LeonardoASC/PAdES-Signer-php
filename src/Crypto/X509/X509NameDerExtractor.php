<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use RuntimeException;

final readonly class X509NameDerExtractor
{
    public function extractIssuerNameDer(
        string $certificatePem
    ): string {
        $fields = $this->extractTbsFields($this->pemToDer($certificatePem));

        return $fields['issuerNameDer'];
    }

    public function extractSubjectNameDer(
        string $certificatePem
    ): string {
        $fields = $this->extractTbsFields($this->pemToDer($certificatePem));

        return $fields['subjectNameDer'];
    }

    public function extractSerialNumberDer(
        string $certificatePem
    ): string {
        $fields = $this->extractTbsFields($this->pemToDer($certificatePem));

        return $fields['serialNumberDer'];
    }

    /**
     * @return array{
     *     serialNumberDer:string,
     *     issuerNameDer:string,
     *     subjectNameDer:string
     * }
     */
    private function extractTbsFields(string $certificateDer): array
    {
        $offset = 0;
        $certificate = $this->readTlv($certificateDer, $offset);

        if ($certificate['tag'] !== 0x30) {
            throw new RuntimeException('Certificado DER invalido.');
        }

        $tbsOffset = $certificate['contentStart'];
        $tbsCertificate = $this->readTlv($certificateDer, $tbsOffset);

        if ($tbsCertificate['tag'] !== 0x30) {
            throw new RuntimeException('TBSCertificate DER invalido.');
        }

        $fieldOffset = $tbsCertificate['contentStart'];

        if ($this->peekTag($certificateDer, $fieldOffset) === 0xA0) {
            $this->readTlv($certificateDer, $fieldOffset);
        }

        $serialNumber = $this->readTlv($certificateDer, $fieldOffset);

        if ($serialNumber['tag'] !== 0x02) {
            throw new RuntimeException('Serial do certificado nao encontrado.');
        }

        $signature = $this->readTlv($certificateDer, $fieldOffset);

        if ($signature['tag'] !== 0x30) {
            throw new RuntimeException('Algoritmo de assinatura do certificado nao encontrado.');
        }

        $issuer = $this->readTlv($certificateDer, $fieldOffset);

        if ($issuer['tag'] !== 0x30) {
            throw new RuntimeException('Issuer do certificado nao encontrado.');
        }

        $validity = $this->readTlv($certificateDer, $fieldOffset);

        if ($validity['tag'] !== 0x30) {
            throw new RuntimeException('Validity do certificado nao encontrada.');
        }

        $subject = $this->readTlv($certificateDer, $fieldOffset);

        if ($subject['tag'] !== 0x30) {
            throw new RuntimeException('Subject do certificado nao encontrado.');
        }

        return [
            'serialNumberDer' => $serialNumber['encoded'],
            'issuerNameDer' => $issuer['encoded'],
            'subjectNameDer' => $subject['encoded'],
        ];
    }

    /**
     * @return array{
     *     tag:int,
     *     length:int,
     *     start:int,
     *     contentStart:int,
     *     end:int,
     *     encoded:string
     * }
     */
    private function readTlv(
        string $der,
        int &$offset
    ): array {
        $length = strlen($der);

        if ($offset >= $length) {
            throw new RuntimeException('Fim inesperado do DER.');
        }

        $start = $offset;
        $tag = ord($der[$offset++]);

        if ($offset >= $length) {
            throw new RuntimeException('Length DER ausente.');
        }

        $firstLengthByte = ord($der[$offset++]);

        if (($firstLengthByte & 0x80) === 0) {
            $contentLength = $firstLengthByte;
        } else {
            $lengthBytes = $firstLengthByte & 0x7F;

            if ($lengthBytes === 0 || $offset + $lengthBytes > $length) {
                throw new RuntimeException('Length DER invalido.');
            }

            $contentLength = 0;

            for ($i = 0; $i < $lengthBytes; $i++) {
                $contentLength = ($contentLength << 8) | ord($der[$offset++]);
            }
        }

        $contentStart = $offset;
        $end = $contentStart + $contentLength;

        if ($end > $length) {
            throw new RuntimeException('Conteudo DER truncado.');
        }

        $offset = $end;

        return [
            'tag' => $tag,
            'length' => $contentLength,
            'start' => $start,
            'contentStart' => $contentStart,
            'end' => $end,
            'encoded' => substr($der, $start, $end - $start),
        ];
    }

    private function peekTag(
        string $der,
        int $offset
    ): ?int {
        if (! isset($der[$offset])) {
            return null;
        }

        return ord($der[$offset]);
    }

    private function pemToDer(
        string $pem
    ): string {
        $clean = preg_replace(
            '/-----BEGIN CERTIFICATE-----|-----END CERTIFICATE-----|\s/',
            '',
            $pem
        );

        if ($clean === null || $clean === '') {
            throw new RuntimeException('PEM do certificado invalido.');
        }

        $der = base64_decode($clean, true);

        if ($der === false) {
            throw new RuntimeException('Nao foi possivel converter certificado PEM para DER.');
        }

        return $der;
    }
}
