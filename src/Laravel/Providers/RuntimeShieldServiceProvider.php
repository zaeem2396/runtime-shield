<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Providers;

use Illuminate\Support\ServiceProvider;
use RuntimeShield\Contracts\ConfigRepositoryContract;
use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Contracts\ShieldContract;
use RuntimeShield\Core\ConfigRepository;
use RuntimeShield\Core\RuntimeShieldManager;
use RuntimeShield\Engine\RuntimeShieldEngine;
use RuntimeShield\Laravel\Console\InstallCommand;

final class RuntimeShieldServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/runtime_shield.php',
            'runtime_shield',
        );

        $this->app->singleton(ConfigRepositoryContract::class, static function ($app): ConfigRepository {
            /** @var array<string, mixed> $raw */
            $raw = $app['config']->get('runtime_shield', []);

            return new ConfigRepository($raw);
        });

        $this->app->singleton(RuntimeShieldManager::class, static fn ($app): RuntimeShieldManager => new RuntimeShieldManager(
            $app->make(ConfigRepositoryContract::class),
        ));

        $this->app->alias(RuntimeShieldManager::class, ShieldContract::class);

        $this->app->singleton(EngineContract::class, static fn ($app): RuntimeShieldEngine => new RuntimeShieldEngine(
            $app->make(RuntimeShieldManager::class),
        ));
    }

    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__ . '/../../../config/runtime_shield.php' => config_path('runtime_shield.php'),
        ], 'runtime-shield-config');

        $this->commands([
            InstallCommand::class,
        ]);
    }
}
