<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Fires when a route has no rate-limiting middleware, leaving the endpoint
 * open to brute-force or denial-of-service abuse.
 */
final class MissingRateLimitRule implements RuleContract
{
    /**
     * Known rate-limiting middleware prefixes.
     *
     * @var list<string>
     */
    private const THROTTLE_PREFIXES = [
        'throttle',
        'rate_limit',
        'rate-limit',
        'ratelimit',
    ];

    public function id(): string
    {
        return 'missing-rate-limit';
    }

    public function title(): string
    {
        return 'Missing Rate Limit';
    }

    public function severity(): Severity
    {
        return Severity::MEDIUM;
    }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        $route = $context->route;

        if ($route === null) {
            return [];
        }

        foreach ($route->middleware as $middleware) {
            foreach (self::THROTTLE_PREFIXES as $prefix) {
                if (str_starts_with($middleware, $prefix)) {
                    return [];
                }
            }
        }

        return [
            new Violation(
                ruleId: $this->id(),
                title: $this->title(),
                description: "Route '{$route->uri}' has no rate-limiting middleware — susceptible to brute-force or DoS.",
                severity: $this->severity(),
                route: $route->uri,
                context: ['middleware' => $route->middleware],
            ),
        ];
    }
}
