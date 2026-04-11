<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Providers;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Support\ServiceProvider;
use RuntimeShield\Contracts\ConfigRepositoryContract;
use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Contracts\Report\ReportBuilderContract;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Contracts\SamplerContract;
use RuntimeShield\Contracts\Score\RuleCategoryMapContract;
use RuntimeShield\Contracts\Score\ScoreEngineContract;
use RuntimeShield\Contracts\ShieldContract;
use RuntimeShield\Contracts\Signal\AuthCollectorContract;
use RuntimeShield\Contracts\Signal\RequestCapturerContract;
use RuntimeShield\Contracts\Signal\ResponseCapturerContract;
use RuntimeShield\Contracts\Signal\RouteCollectorContract;
use RuntimeShield\Contracts\Signal\RuntimeContextStoreContract;
use RuntimeShield\Contracts\Signal\SignalPipelineContract;
use RuntimeShield\Contracts\Signal\SignalStoreContract;
use RuntimeShield\Core\ConfigRepository;
use RuntimeShield\Core\Performance\AsyncRuleEngine;
use RuntimeShield\Core\Performance\BatchedRuleEngine;
use RuntimeShield\Core\Performance\MetricsStore;
use RuntimeShield\Core\Performance\NullSignalPipeline;
use RuntimeShield\Core\Report\ReportBuilder;
use RuntimeShield\Core\Report\RouteProtectionAnalyzer;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\Core\RuntimeShieldManager;
use RuntimeShield\Core\Sampling\SamplerFactory;
use RuntimeShield\Core\Score\RuleCategoryMap;
use RuntimeShield\Core\Score\ScoreEngine;
use RuntimeShield\Core\Signal\InMemoryContextStore;
use RuntimeShield\Core\Signal\InMemorySignalStore;
use RuntimeShield\Engine\RuntimeShieldEngine;
use RuntimeShield\Laravel\Console\BenchCommand;
use RuntimeShield\Laravel\Console\InstallCommand;
use RuntimeShield\Laravel\Console\ReportCommand;
use RuntimeShield\Laravel\Console\RoutesCommand;
use RuntimeShield\Laravel\Console\SamplingCommand;
use RuntimeShield\Laravel\Console\ScanCommand;
use RuntimeShield\Laravel\Console\ScoreCommand;
use RuntimeShield\Laravel\Signal\AuthSignalCollector;
use RuntimeShield\Laravel\Signal\RequestCapturer;
use RuntimeShield\Laravel\Signal\ResponseCapturer;
use RuntimeShield\Laravel\Signal\RouteSignalCollector;
use RuntimeShield\Laravel\Signal\SignalPipeline;
use RuntimeShield\Rules\FileUploadValidationRule;
use RuntimeShield\Rules\MissingCsrfRule;
use RuntimeShield\Rules\MissingRateLimitRule;
use RuntimeShield\Rules\MissingValidationRule;
use RuntimeShield\Rules\PublicRouteWithoutAuthRule;

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

        $this->app->singleton(SamplerContract::class, static function ($app): SamplerContract {
            /** @var array<string, mixed> $raw */
            $raw = $app['config']->get('runtime_shield', []);
            $env = is_string($app->environment()) ? $app->environment() : 'production';

            return SamplerFactory::fromConfig($raw, $env);
        });

        $this->app->singleton(MetricsStore::class, static fn (): MetricsStore => new MetricsStore());

        $this->app->singleton(SignalStoreContract::class, static fn (): InMemorySignalStore => new InMemorySignalStore());

        $this->app->singleton(RequestCapturerContract::class, static fn (): RequestCapturer => new RequestCapturer());

        $this->app->singleton(ResponseCapturerContract::class, static fn (): ResponseCapturer => new ResponseCapturer());

        $this->app->singleton(RouteCollectorContract::class, static fn (): RouteSignalCollector => new RouteSignalCollector());

        $this->app->singleton(AuthCollectorContract::class, static fn ($app): AuthSignalCollector => new AuthSignalCollector(
            $app->make(AuthFactory::class),
        ));

        $this->app->singleton(RuntimeContextStoreContract::class, static fn (): InMemoryContextStore => new InMemoryContextStore());

        $this->app->singleton(SignalPipelineContract::class, static function ($app): SignalPipelineContract {
            $enabled = (bool) $app['config']->get('runtime_shield.enabled', true);

            if (! $enabled) {
                return new NullSignalPipeline();
            }

            return new SignalPipeline(
                $app->make(SamplerContract::class),
                $app->make(SignalStoreContract::class),
                $app->make(RuntimeContextStoreContract::class),
                $app->make(RequestCapturerContract::class),
                $app->make(ResponseCapturerContract::class),
                $app->make(RouteCollectorContract::class),
                $app->make(AuthCollectorContract::class),
            );
        });

        $this->app->singleton(RuleRegistry::class, static function (): RuleRegistry {
            $registry = new RuleRegistry();
            $registry->register(new PublicRouteWithoutAuthRule());
            $registry->register(new MissingRateLimitRule());
            $registry->register(new MissingCsrfRule());
            $registry->register(new MissingValidationRule());
            $registry->register(new FileUploadValidationRule());

            return $registry;
        });

        $this->app->singleton(RuleEngineContract::class, static function ($app): RuleEngineContract {
            $registry = $app->make(RuleRegistry::class);
            $batchSize = (int) $app['config']->get('runtime_shield.performance.batch_size', 50);
            $timeoutMs = (int) $app['config']->get('runtime_shield.performance.timeout_ms', 100);
            $async = (bool) $app['config']->get('runtime_shield.performance.async', false);

            $baseEngine = new BatchedRuleEngine($registry, $batchSize, $timeoutMs);

            return new AsyncRuleEngine($baseEngine, $async);
        });

        $this->app->singleton(EngineContract::class, static fn ($app): RuntimeShieldEngine => new RuntimeShieldEngine(
            $app->make(RuntimeShieldManager::class),
            $app->make(RuleEngineContract::class),
        ));

        $this->app->singleton(RouteProtectionAnalyzer::class, static fn (): RouteProtectionAnalyzer => new RouteProtectionAnalyzer());

        $this->app->singleton(ReportBuilderContract::class, static fn ($app): ReportBuilder => new ReportBuilder(
            $app->make(\Illuminate\Routing\Router::class),
            $app->make(RuleEngineContract::class),
            $app->make(RouteProtectionAnalyzer::class),
        ));

        $this->app->singleton(RuleCategoryMapContract::class, static fn (): RuleCategoryMap => new RuleCategoryMap());

        $this->app->singleton(ScoreEngineContract::class, static function ($app): ScoreEngine {
            /** @var array<string, int> $weights */
            $weights = (array) $app['config']->get('runtime_shield.scoring.weights', []);

            return new ScoreEngine(
                $app->make(RuleCategoryMapContract::class),
                $weights,
            );
        });
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
            ScanCommand::class,
            ReportCommand::class,
            RoutesCommand::class,
            ScoreCommand::class,
            BenchCommand::class,
            SamplingCommand::class,
        ]);
    }
}
