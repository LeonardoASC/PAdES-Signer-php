<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Signing;

final class RemoteSignerProvider extends CallbackSignerProvider
{
    public function __construct(callable $callback)
    {
        parent::__construct($callback, 'remote-signing');
    }
}
