<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Fires when a mutable (non-GET) web route is not covered by CSRF protection.
 *
 * API routes (carrying the "api" middleware group) are excluded because they
 * typically use token-based authentication and do not require CSRF cookies.
 */
final class MissingCsrfRule implements RuleContract
{
    /** HTTP methods that mutate state and therefore require CSRF protection. */
    private const MUTABLE_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** Middleware that implies CSRF protection is active. */
    private const CSRF_MIDDLEWARE = ['web', 'csrf', 'verify-csrf-token'];

    /** Middleware groups that indicate an API context (CSRF not applicable). */
    private const API_MIDDLEWARE = ['api', 'api:sanctum', 'stateless'];

    public function id(): string
    {
        return 'missing-csrf-protection';
    }

    public function title(): string
    {
        return 'Missing CSRF Protection';
    }

    public function severity(): Severity
    {
        return Severity::HIGH;
    }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        $route   = $context->route;
        $request = $context->request;

        if ($route === null || $request === null) {
            return [];
        }

        if (! in_array(strtoupper($request->method), self::MUTABLE_METHODS, true)) {
            return [];
        }

        foreach ($route->middleware as $middleware) {
            foreach (self::API_MIDDLEWARE as $api) {
                if (str_starts_with($middleware, $api)) {
                    return [];
                }
            }

            foreach (self::CSRF_MIDDLEWARE as $csrf) {
                if (str_starts_with($middleware, $csrf)) {
                    return [];
                }
            }
        }

        return [
            new Violation(
                ruleId: $this->id(),
                title: $this->title(),
                description: "Route '{$route->uri}' ({$request->method}) is a mutable web route with no CSRF middleware.",
                severity: $this->severity(),
                route: $route->uri,
                context: ['method' => $request->method, 'middleware' => $route->middleware],
            ),
        ];
    }
}
