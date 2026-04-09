<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Middleware;

use Illuminate\Http\Request;
use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Core\RuntimeShieldManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * HTTP middleware that boots the RuntimeShield engine per request.
 *
 * Placement: add to the global middleware stack (or per-route group)
 * in your application's HTTP kernel / middleware alias list.
 *
 * When the shield is disabled the entire handle() body is a single
 * $next($request) call — no allocations, no lookups.
 */
final class RuntimeShieldMiddleware
{
    public function __construct(
        private readonly RuntimeShieldManager $manager,
        private readonly EngineContract $engine,
    ) {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        if (! $this->manager->isEnabled()) {
            return $next($request);
        }

        $this->engine->boot();

        return $next($request);
    }
}
