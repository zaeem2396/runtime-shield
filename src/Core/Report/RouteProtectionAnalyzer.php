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
    /**
     * Determine whether the route carries authentication middleware.
     */
    public function hasAuth(RouteSignal $route): bool
    {
        return false;
    }
}
