<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

use NihilLabs\Pades\Crypto\Algorithm\SignatureAlgorithmPolicy;
use RuntimeException;

class CallbackSignerProvider implements SignerProviderInterface
{
    private \Closure $callback;

    public function __construct(
        callable $callback,
        private readonly string $providerType = 'callback'
    ) {
        $this->callback = \Closure::fromCallable($callback);
    }

    public function sign(
        string $data,
        SignatureCredentialInterface $credential,
        SignatureAlgorithmPolicy $algorithmPolicy = new SignatureAlgorithmPolicy()
    ): string {
        $signature = ($this->callback)(
            $data,
            $credential,
            $algorithmPolicy
        );

        if (! is_string($signature) || $signature === '') {
            throw new RuntimeException(
                "O signer provider {$this->providerType} nao retornou uma assinatura binaria valida."
            );
        }

        return $signature;
    }

    public function getProviderType(): string
    {
        return $this->providerType;
    }
}
