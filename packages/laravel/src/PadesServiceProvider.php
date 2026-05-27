<?php

declare(strict_types=1);

namespace NihilLabs\Pades\Laravel;

use Illuminate\Support\ServiceProvider;

final class PadesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/pades.php',
            'pades'
        );

        $this->app->singleton(PadesManager::class);
        $this->app->alias(PadesManager::class, 'pades');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/pades.php' => config_path('pades.php'),
        ], 'pades-config');
    }
}
