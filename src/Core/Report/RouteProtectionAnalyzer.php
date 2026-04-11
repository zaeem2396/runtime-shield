<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Report;

use RuntimeShield\DTO\Signal\RouteSignal;

/**
 * Analyzes a RouteSignal's middleware list and determines which security
 * protections are active (auth, CSRF, rate-limiting).
 *
 * All methods are pure and stateless — safe to use as a singleton.
 */
final class RouteProtectionAnalyzer
{
    /** @var list<string> */
    private const AUTH_PREFIXES = [
        'auth',
        'can:',
        'permission:',
        'role:',
        'sanctum',
        'passport',
        'verified',
    ];

    /**
     * Determine whether the route carries authentication middleware.
     */
    public function hasAuth(RouteSignal $route): bool
    {
        foreach ($route->middleware as $mw) {
            foreach (self::AUTH_PREFIXES as $prefix) {
                if (str_starts_with($mw, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Determine whether CSRF protection is active.
     *
     * Returns true for API routes (CSRF is not applicable) and for any route
     * carrying 'web' or 'csrf' middleware.
     */
    public function hasCsrf(RouteSignal $route, string $method): bool
    {
        if ($this->isApiRoute($route)) {
            return true;
        }

        $mutableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

        if (! in_array(strtoupper($method), $mutableMethods, true)) {
            return true;
        }

        foreach ($route->middleware as $mw) {
            if (str_starts_with($mw, 'web') || str_starts_with($mw, 'csrf')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine whether rate-limiting middleware is present on the route.
     */
    public function hasRateLimit(RouteSignal $route): bool
    {
        $prefixes = ['throttle', 'rate_limit', 'rate-limit', 'ratelimit'];

        foreach ($route->middleware as $mw) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($mw, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isApiRoute(RouteSignal $route): bool
    {
        foreach ($route->middleware as $mw) {
            if (str_starts_with($mw, 'api')) {
                return true;
            }
        }

        return str_starts_with($route->uri, 'api/') || str_starts_with($route->uri, 'api');
    }
}
