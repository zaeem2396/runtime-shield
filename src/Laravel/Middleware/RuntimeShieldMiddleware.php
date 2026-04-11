<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Middleware;

use Illuminate\Http\Request;
use RuntimeShield\Contracts\Alert\AlertDispatcherContract;
use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Contracts\Signal\SignalPipelineContract;
use RuntimeShield\Core\Performance\MetricsStore;
use RuntimeShield\Core\RuntimeShieldManager;
use RuntimeShield\DTO\Performance\MiddlewareMetrics;
use RuntimeShield\Laravel\Jobs\AlertDispatchJob;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP middleware that boots the RuntimeShield engine and drives the
 * two-phase signal pipeline.
 *
 * Zero-overhead path: when the shield is disabled the entire body of
 * handle() reduces to a single $next($request) call.
 *
 * When alerts are enabled, terminate() also evaluates rules and dispatches
 * an AlertDispatcherContract after the HTTP response has been sent, so the
 * client never waits for alert delivery.
 */
final class RuntimeShieldMiddleware
{
    private float|null $startTimeMs = null;

    private int $memoryBefore = 0;

    public function __construct(
        private readonly RuntimeShieldManager $manager,
        private readonly EngineContract $engine,
        private readonly SignalPipelineContract $pipeline,
        private readonly MetricsStore $metricsStore,
        private readonly AlertDispatcherContract $alertDispatcher,
        private readonly bool $alertsEnabled,
        private readonly bool $alertsAsync,
    ) {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if (! $this->manager->isEnabled()) {
            return $next($request);
        }

        $this->startTimeMs = microtime(true) * 1000.0;
        $this->memoryBefore = memory_get_usage();
        $this->engine->boot();
        $this->pipeline->collectRequest($request);

        return $next($request);
    }

    /**
     * Called by Laravel after the response has been sent to the client.
     * Assembles the full SecurityRuntimeContext without blocking HTTP.
     */
    public function terminate(Request $request, Response $response): void
    {
        if (! $this->manager->isEnabled() || $this->startTimeMs === null) {
            return;
        }

        $context = $this->pipeline->assemble($response, $this->startTimeMs);

        $processingMs = (microtime(true) * 1000.0) - $this->startTimeMs;
        $memoryDeltaKb = (int) round((memory_get_usage() - $this->memoryBefore) / 1024);
        $wasSampled = $context !== null;
        $rulesEvaluated = 0;

        if ($context !== null && $this->alertsEnabled) {
            $violations = $this->engine->evaluate($context);
            $rulesEvaluated = $violations->count();

            if (! $violations->isEmpty()) {
                $routeSignal = $context->route;
                $route = $routeSignal !== null
                    ? ($routeSignal->name !== '' ? $routeSignal->name : $routeSignal->uri)
                    : '';

                if ($this->alertsAsync) {
                    AlertDispatchJob::dispatch($violations, $route);
                } else {
                    $this->alertDispatcher->dispatch($violations, $route);
                }
            }
        }

        $this->metricsStore->push(new MiddlewareMetrics(
            processingMs: max(0.0, $processingMs),
            memoryDeltaKb: $memoryDeltaKb,
            wasSampled: $wasSampled,
            rulesEvaluated: $rulesEvaluated,
            capturedAt: new \DateTimeImmutable(),
        ));
    }
}
