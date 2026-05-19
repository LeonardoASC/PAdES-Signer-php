<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspRealtimeValidator
{
    public function __construct(
        private ?OcspUrlResolver $urlResolver = null,
        private ?OcspRequestBuilder $requestBuilder = null,
        private ?OcspClient $client = null,
        private ?OcspValidator $validator = null
    ) {}

    public function validate(
        string $certificatePem,
        string $issuerNameDer,
        string $issuerPublicKeyDer,
        string $serialNumberHex
    ): OcspValidationResult {
        $urlResolver = $this->urlResolver
            ?? new OcspUrlResolver();

        $requestBuilder = $this->requestBuilder
            ?? new OcspRequestBuilder();

        $client = $this->client
            ?? new OcspClient();

        $validator = $this->validator
            ?? new OcspValidator();

        $url = $urlResolver->resolve(
            $certificatePem
        );

        if ($url === null) {
            return new OcspValidationResult(
                successful: false,
                certificateStatus: null
            );
        }

        $request = $requestBuilder->build(
            issuerNameDer: $issuerNameDer,
            issuerPublicKeyDer: $issuerPublicKeyDer,
            serialNumberHex: $serialNumberHex
        );

        $response = $client->request(
            $url,
            $request
        );

        if ($response === null) {
            return new OcspValidationResult(
                successful: false,
                certificateStatus: null
            );
        }

        return $validator->validate(
            $response
        );
    }
}