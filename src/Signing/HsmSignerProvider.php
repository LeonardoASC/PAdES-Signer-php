<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

final class HsmSignerProvider extends CallbackSignerProvider
{
    public function __construct(callable $callback)
    {
        parent::__construct($callback, 'hsm');
    }
}
