<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Providers;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;
use RuntimeShield\Contracts\Advisory\ViolationAdvisoryEnricherContract;
use RuntimeShield\Contracts\Alert\AlertDispatcherContract;
use RuntimeShield\Contracts\ConfigRepositoryContract;
use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Contracts\EventEmitterContract;
use RuntimeShield\Contracts\Http\HttpTransportContract;
use RuntimeShield\Contracts\Plugin\PluginContract;
use RuntimeShield\Contracts\Report\ReportBuilderContract;
use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Contracts\Rule\RuleRegistrarContract;
use RuntimeShield\Contracts\SamplerContract;
use RuntimeShield\Contracts\Score\RuleCategoryMapContract;
use RuntimeShield\Contracts\Score\ScoreEngineContract;
use RuntimeShield\Contracts\ShieldContract;
use RuntimeShield\Contracts\Signal\AuthCollectorContract;
use RuntimeShield\Contracts\Signal\CustomSignalCollectorContract;
use RuntimeShield\Contracts\Signal\RequestCapturerContract;
use RuntimeShield\Contracts\Signal\ResponseCapturerContract;
use RuntimeShield\Contracts\Signal\RouteCollectorContract;
use RuntimeShield\Contracts\Signal\RuntimeContextStoreContract;
use RuntimeShield\Contracts\Signal\SignalPipelineContract;
use RuntimeShield\Contracts\Signal\SignalStoreContract;
use RuntimeShield\Core\Advisory\AdvisoryBatchProgress;
use RuntimeShield\Core\Advisory\NullViolationAdvisoryEnricher;
use RuntimeShield\Core\Advisory\OpenAiViolationAdvisoryEnricher;
use RuntimeShield\Core\Alert\AlertDispatcher;
use RuntimeShield\Core\Alert\AlertThrottle;
use RuntimeShield\Core\Alert\LogChannel;
use RuntimeShield\Core\Alert\MailChannel;
use RuntimeShield\Core\Alert\NullAlertChannel;
use RuntimeShield\Core\Alert\SlackChannel;
use RuntimeShield\Core\Alert\ThrottledAlertDispatcher;
use RuntimeShield\Core\Alert\WebhookChannel;
use RuntimeShield\Core\ConfigRepository;
use RuntimeShield\Core\Http\StreamHttpTransport;
use RuntimeShield\Core\NullEventEmitter;
use RuntimeShield\Core\Performance\AsyncRuleEngine;
use RuntimeShield\Core\Performance\BatchedRuleEngine;
use RuntimeShield\Core\Performance\MetricsStore;
use RuntimeShield\Core\Performance\NullSignalPipeline;
use RuntimeShield\Core\Plugin\PluginRegistry;
use RuntimeShield\Core\Report\ReportBuilder;
use RuntimeShield\Core\Report\RouteProtectionAnalyzer;
use RuntimeShield\Core\Rule\RuleRegistrar;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\Core\RuntimeShieldManager;
use RuntimeShield\Core\Sampling\SamplerFactory;
use RuntimeShield\Core\Score\RuleCategoryMap;
use RuntimeShield\Core\Score\ScoreEngine;
use RuntimeShield\Core\Signal\CustomSignalRegistry;
use RuntimeShield\Core\Signal\CustomSignalStore;
use RuntimeShield\Core\Signal\InMemoryContextStore;
use RuntimeShield\Core\Signal\InMemorySignalStore;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\Engine\RuntimeShieldEngine;
use RuntimeShield\Laravel\Console\AlertsCommand;
use RuntimeShield\Laravel\Console\BenchCommand;
use RuntimeShield\Laravel\Console\InstallCommand;
use RuntimeShield\Laravel\Console\ReportCommand;
use RuntimeShield\Laravel\Console\RoutesCommand;
use RuntimeShield\Laravel\Console\SamplingCommand;
use RuntimeShield\Laravel\Console\ScanCommand;
use RuntimeShield\Laravel\Console\ScoreCommand;
use RuntimeShield\Laravel\LaravelEventEmitter;
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
                $app->make(CustomSignalRegistry::class),
                $app->make(CustomSignalStore::class),
            );
        });

        $this->app->singleton(CustomSignalStore::class, static fn (): CustomSignalStore => new CustomSignalStore());

        $this->app->singleton(CustomSignalRegistry::class, static fn (): CustomSignalRegistry => new CustomSignalRegistry());

        $this->app->singleton(PluginRegistry::class, static fn (): PluginRegistry => new PluginRegistry());

        $this->app->singleton(RuleRegistry::class, static function (): RuleRegistry {
            $registry = new RuleRegistry();
            $registry->register(new PublicRouteWithoutAuthRule());
            $registry->register(new MissingRateLimitRule());
            $registry->register(new MissingCsrfRule());
            $registry->register(new MissingValidationRule());
            $registry->register(new FileUploadValidationRule());

            return $registry;
        });

        $this->app->singleton(RuleRegistrar::class, static fn ($app): RuleRegistrar => new RuleRegistrar(
            $app->make(RuleRegistry::class),
        ));

        $this->app->alias(RuleRegistrar::class, RuleRegistrarContract::class);

        // BatchedRuleEngine is bound as its own singleton so that CLI commands
        // needing real synchronous evaluation (e.g. BenchCommand) can inject
        // it directly and always bypass the async wrapper.
        $this->app->singleton(BatchedRuleEngine::class, static function ($app): BatchedRuleEngine {
            $batchSize = (int) $app['config']->get('runtime_shield.performance.batch_size', 50);
            $timeoutMs = (int) $app['config']->get('runtime_shield.performance.timeout_ms', 100);

            return new BatchedRuleEngine($app->make(RuleRegistry::class), $batchSize, $timeoutMs);
        });

        $this->app->singleton(RuleEngineContract::class, static function ($app): RuleEngineContract {
            $async = (bool) $app['config']->get('runtime_shield.performance.async', false);

            return new AsyncRuleEngine($app->make(BatchedRuleEngine::class), $async);
        });

        $this->app->singleton(EventEmitterContract::class, static function ($app): EventEmitterContract {
            $eventsEnabled = (bool) $app['config']->get('runtime_shield.events.enabled', true);

            if (! $eventsEnabled || ! $app->bound(EventDispatcher::class)) {
                return new NullEventEmitter();
            }

            return new LaravelEventEmitter($app->make(EventDispatcher::class));
        });

        $this->app->singleton(HttpTransportContract::class, static fn (): HttpTransportContract => new StreamHttpTransport());

        $this->app->singleton(AdvisoryBatchProgress::class, static fn (): AdvisoryBatchProgress => new AdvisoryBatchProgress());

        $this->app->singleton(ViolationAdvisoryEnricherContract::class, static function ($app): ViolationAdvisoryEnricherContract {
            /** @var array<string, mixed> $ai */
            $ai = (array) $app['config']->get('runtime_shield.ai', []);
            $enabled = (bool) ($ai['enabled'] ?? false);
            $apiKey = isset($ai['api_key']) && is_string($ai['api_key']) ? $ai['api_key'] : '';

            if (! $enabled || $apiKey === '') {
                return new NullViolationAdvisoryEnricher();
            }

            $logger = $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null;

            return new OpenAiViolationAdvisoryEnricher(
                $ai,
                $app->make(HttpTransportContract::class),
                $logger,
                $app->make(AdvisoryBatchProgress::class),
            );
        });

        $this->app->singleton(EngineContract::class, static fn ($app): RuntimeShieldEngine => new RuntimeShieldEngine(
            $app->make(RuntimeShieldManager::class),
            $app->make(RuleEngineContract::class),
            $app->make(ViolationAdvisoryEnricherContract::class),
            $app->make(RuleRegistry::class),
            $app->make(EventEmitterContract::class),
        ));

        $this->app->singleton(\RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware::class, static function ($app): \RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware {
            $alertsEnabled = (bool) $app['config']->get('runtime_shield.alerts.enabled', false);
            $alertsAsync = (bool) $app['config']->get('runtime_shield.alerts.async', false);

            return new \RuntimeShield\Laravel\Middleware\RuntimeShieldMiddleware(
                $app->make(RuntimeShieldManager::class),
                $app->make(EngineContract::class),
                $app->make(SignalPipelineContract::class),
                $app->make(MetricsStore::class),
                $app->make(AlertDispatcherContract::class),
                $alertsEnabled,
                $alertsAsync,
            );
        });

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

        $this->app->singleton(AlertDispatcherContract::class, static function ($app): AlertDispatcherContract {
            $alertsEnabled = (bool) $app['config']->get('runtime_shield.alerts.enabled', false);

            if (! $alertsEnabled) {
                $dispatcher = new AlertDispatcher(Severity::HIGH);
                $dispatcher->addChannel(new NullAlertChannel());

                return $dispatcher;
            }

            $minSeverityValue = (string) $app['config']->get('runtime_shield.alerts.min_severity', 'high');
            $minSeverity = Severity::tryFrom($minSeverityValue) ?? Severity::HIGH;
            $throttleSeconds = (int) $app['config']->get('runtime_shield.alerts.throttle_seconds', 300);

            $dispatcher = new AlertDispatcher($minSeverity);

            // Log channel
            /** @var array<string, mixed> $logConfig */
            $logConfig = (array) $app['config']->get('runtime_shield.alerts.channels.log', []);
            $logEnabled = isset($logConfig['enabled']) && (bool) $logConfig['enabled'];
            $dispatcher->addChannel(new LogChannel($logEnabled, $app->make(LoggerInterface::class)));

            // Webhook channel
            /** @var array<string, mixed> $webhookConfig */
            $webhookConfig = (array) $app['config']->get('runtime_shield.alerts.channels.webhook', []);
            $webhookEnabled = isset($webhookConfig['enabled']) && (bool) $webhookConfig['enabled'];
            /** @var array<string, string> $webhookHeaders */
            $webhookHeaders = isset($webhookConfig['headers']) && is_array($webhookConfig['headers'])
                ? $webhookConfig['headers'] : [];
            $dispatcher->addChannel(new WebhookChannel(
                $webhookEnabled,
                is_string($webhookConfig['url'] ?? null) ? (string) $webhookConfig['url'] : '',
                is_string($webhookConfig['method'] ?? null) ? (string) $webhookConfig['method'] : 'POST',
                $webhookHeaders,
            ));

            // Slack channel
            /** @var array<string, mixed> $slackConfig */
            $slackConfig = (array) $app['config']->get('runtime_shield.alerts.channels.slack', []);
            $slackEnabled = isset($slackConfig['enabled']) && (bool) $slackConfig['enabled'];
            $dispatcher->addChannel(new SlackChannel(
                $slackEnabled,
                is_string($slackConfig['url'] ?? null) ? (string) $slackConfig['url'] : '',
            ));

            // Mail channel — sender closure delegates to Laravel Mailer
            /** @var array<string, mixed> $mailConfig */
            $mailConfig = (array) $app['config']->get('runtime_shield.alerts.channels.mail', []);
            $mailEnabled = isset($mailConfig['enabled']) && (bool) $mailConfig['enabled'];
            /** @var list<string> $mailRecipients */
            $mailRecipients = isset($mailConfig['recipients']) && is_array($mailConfig['recipients'])
                ? array_values(array_filter($mailConfig['recipients'], 'is_string'))
                : [];
            $mailFrom = is_string($mailConfig['from'] ?? null) ? (string) $mailConfig['from'] : '';

            $mailSend = static function (string $subject, string $body, array $recipients, string $from) use ($app): void {
                /** @var \Illuminate\Contracts\Mail\Mailer $mailer */
                $mailer = $app->make(\Illuminate\Contracts\Mail\Mailer::class);
                $mailer->raw($body, static function (object $message) use ($subject, $recipients, $from): void {
                    if (method_exists($message, 'to')) {
                        $message->to($recipients)->from($from)->subject($subject);
                    }
                });
            };

            $dispatcher->addChannel(new MailChannel($mailEnabled, $mailRecipients, $mailFrom, $mailSend));

            // Wrap with throttle decorator
            if ($throttleSeconds > 0) {
                return new ThrottledAlertDispatcher($dispatcher, new AlertThrottle($throttleSeconds));
            }

            return $dispatcher;
        });
    }

    public function boot(): void
    {
        $this->bootPlugins();
        $this->registerCustomRules();
        $this->registerCustomSignalCollectors();

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
            AlertsCommand::class,
            \RuntimeShield\Laravel\Console\PluginsCommand::class,
        ]);
    }

    /**
     * Resolve plugin class names from extensibility.plugins, instantiate them,
     * register into PluginRegistry, then boot all plugins to propagate their
     * rules and signal collectors into their respective registries.
     */
    private function bootPlugins(): void
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->make('config');
        /** @var list<mixed> $pluginClasses */
        $pluginClasses = (array) $config->get('runtime_shield.extensibility.plugins', []);

        $pluginRegistry = $this->app->make(PluginRegistry::class);

        foreach ($pluginClasses as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $plugin = $this->app->make($class);

            if ($plugin instanceof PluginContract) {
                $pluginRegistry->register($plugin);
            }
        }

        if ($pluginRegistry->count() > 0) {
            $pluginRegistry->boot(
                $this->app->make(RuleRegistry::class),
                $this->app->make(CustomSignalRegistry::class),
            );
        }
    }

    /**
     * Resolve any custom signal collector class names declared in
     * extensibility.signal_collectors and register them into the singleton
     * CustomSignalRegistry so the pipeline picks them up at boot time.
     */
    private function registerCustomSignalCollectors(): void
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->make('config');
        /** @var list<mixed> $collectorClasses */
        $collectorClasses = (array) $config->get('runtime_shield.extensibility.signal_collectors', []);

        if ($collectorClasses === []) {
            return;
        }

        $registry = $this->app->make(CustomSignalRegistry::class);

        foreach ($collectorClasses as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $collector = $this->app->make($class);

            if ($collector instanceof CustomSignalCollectorContract) {
                $registry->register($collector);
            }
        }
    }

    /**
     * Resolve any custom rule class names declared in extensibility.rules
     * and register them into the shared RuleRegistry singleton.
     */
    private function registerCustomRules(): void
    {
        /** @var \Illuminate\Config\Repository $config */
        $config = $this->app->make('config');
        /** @var list<mixed> $ruleClasses */
        $ruleClasses = (array) $config->get('runtime_shield.extensibility.rules', []);

        if ($ruleClasses === []) {
            return;
        }

        $registry = $this->app->make(RuleRegistry::class);

        foreach ($ruleClasses as $class) {
            if (! is_string($class) || ! class_exists($class)) {
                continue;
            }

            $rule = $this->app->make($class);

            if ($rule instanceof RuleContract) {
                $registry->register($rule);
            }
        }
    }
}
