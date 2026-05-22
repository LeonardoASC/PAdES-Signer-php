<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

final class Pkcs11SignerProvider extends CallbackSignerProvider
{
    public function __construct(callable $callback)
    {
        parent::__construct($callback, 'pkcs11');
    }
}
