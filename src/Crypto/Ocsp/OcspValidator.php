<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\Ocsp;

final readonly class OcspValidator
{
    public function validate(
        string $responseDer
    ): OcspValidationResult {
        if (! (new OcspResponseParser())->isParsable($responseDer)) {
            return new OcspValidationResult(
                successful: false,
                certificateStatus: null
            );
        }

        if (! (new OcspResponseStatusExtractor())->isSuccessful($responseDer)) {
            return new OcspValidationResult(
                successful: false,
                certificateStatus: null
            );
        }

        $basicResponse = (new BasicOcspResponseExtractor())
            ->extract($responseDer);

        if ($basicResponse === null) {
            return new OcspValidationResult(
                successful: true,
                certificateStatus: null
            );
        }

        $status = (new CertStatusExtractor())
            ->extract($basicResponse);

        return new OcspValidationResult(
            successful: true,
            certificateStatus: $status
        );
    }
}