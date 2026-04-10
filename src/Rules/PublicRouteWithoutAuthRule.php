<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Fires when a route has no authentication middleware, making it publicly
 * accessible without any identity verification.
 */
final class PublicRouteWithoutAuthRule implements RuleContract
{
    /**
     * Common authentication middleware patterns (prefix match).
     *
     * @var list<string>
     */
    private const AUTH_PREFIXES = [
        'auth',
        'can:',
        'permission:',
        'role:',
        'sanctum',
        'passport',
        'verified',
    ];

    public function id(): string
    {
        return 'public-route-without-auth';
    }

    public function title(): string
    {
        return 'Public Route Without Authentication';
    }

    public function severity(): Severity
    {
        return Severity::CRITICAL;
    }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        $route = $context->route;

        if ($route === null) {
            return [];
        }

        foreach ($route->middleware as $middleware) {
            foreach (self::AUTH_PREFIXES as $prefix) {
                if (str_starts_with($middleware, $prefix)) {
                    return [];
                }
            }
        }

        return [
            new Violation(
                ruleId: $this->id(),
                title: $this->title(),
                description: "Route '{$route->uri}' is publicly accessible — no authentication middleware detected.",
                severity: $this->severity(),
                route: $route->uri,
                context: ['middleware' => $route->middleware],
            ),
        ];
    }
}
