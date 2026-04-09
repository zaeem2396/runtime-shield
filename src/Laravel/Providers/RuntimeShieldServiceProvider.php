<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Providers;

use Illuminate\Support\ServiceProvider;

final class RuntimeShieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/runtime_shield.php',
            'runtime_shield',
        );
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../../config/runtime_shield.php' => config_path('runtime_shield.php'),
        ], 'runtime-shield-config');
    }
}
