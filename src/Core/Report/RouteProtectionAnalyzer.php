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
}
