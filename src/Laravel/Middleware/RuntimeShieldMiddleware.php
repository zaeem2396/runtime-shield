<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Middleware;

use Illuminate\Http\Request;
use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Contracts\Signal\SignalPipelineContract;
use RuntimeShield\Core\RuntimeShieldManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP middleware that boots the RuntimeShield engine and drives the
 * two-phase signal pipeline.
 *
 * Zero-overhead path: when the shield is disabled the entire body of
 * handle() reduces to a single $next($request) call.
 */
final class RuntimeShieldMiddleware
{
    private float|null $startTimeMs = null;

    public function __construct(
        private readonly RuntimeShieldManager $manager,
        private readonly EngineContract $engine,
        private readonly SignalPipelineContract $pipeline,
    ) {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if (! $this->manager->isEnabled()) {
            return $next($request);
        }

        $this->startTimeMs = microtime(true) * 1000.0;
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

        $this->pipeline->assemble($response, $this->startTimeMs);
    }
}
