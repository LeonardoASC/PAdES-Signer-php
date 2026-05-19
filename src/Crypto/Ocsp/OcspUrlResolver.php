<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

use NihilLabs\Pades\Crypto\X509\AuthorityInfoAccessParser;
use NihilLabs\Pades\Crypto\X509\X509ExtensionExtractor;

final readonly class OcspUrlResolver
{
    public function __construct(
        private ?X509ExtensionExtractor $extractor = null,
        private ?AuthorityInfoAccessParser $parser = null
    ) {}

    public function resolve(
        string $certificatePem
    ): ?string {
        $extractor = $this->extractor
            ?? new X509ExtensionExtractor();

        $parser = $this->parser
            ?? new AuthorityInfoAccessParser();

        $aia = $extractor->authorityInfoAccess(
            $certificatePem
        );

        if ($aia === null) {
            return null;
        }

        return $parser->ocspUrl($aia);
    }
}