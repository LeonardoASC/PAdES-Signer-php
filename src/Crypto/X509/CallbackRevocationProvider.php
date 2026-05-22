<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Crypto\X509;

use RuntimeException;

final class CallbackRevocationProvider implements RevocationProviderInterface
{
    private \Closure $callback;

    public function __construct(callable $callback)
    {
        $this->callback = \Closure::fromCallable($callback);
    }

    public function collect(array $certificateChainPem): RevocationMaterial
    {
        $material = ($this->callback)($certificateChainPem);

        if (! $material instanceof RevocationMaterial) {
            throw new RuntimeException(
                'Revocation provider deve retornar uma instancia de RevocationMaterial.'
            );
        }

        return $material;
    }
}
