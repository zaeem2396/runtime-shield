<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Flags likely brute-force exposure patterns on authentication endpoints.
 */
final class BruteForcePatternRule implements RuleContract
{
    /** @var list<string> */
    private const AUTH_ROUTE_KEYWORDS = [
        'login',
        'signin',
        'auth',
        'token',
        'password',
        'otp',
        'verify',
    ];

    /** @var list<string> */
    private const THROTTLE_PREFIXES = [
        'throttle',
        'rate_limit',
        'rate-limit',
        'ratelimit',
    ];

    public function id(): string
    {
        return 'brute-force-pattern-detected';
    }

    public function title(): string
    {
        return 'Brute Force Pattern Detected';
    }

    public function severity(): Severity
    {
        return Severity::HIGH;
    }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        $route = $context->route;
        $request = $context->request;
        $response = $context->response;

        if ($route === null || $request === null || $response === null) {
            return [];
        }

        if ($response->statusCode !== 401) {
            return [];
        }

        $target = strtolower($route->uri . ' ' . $request->path);
        $looksLikeAuthEndpoint = false;

        foreach (self::AUTH_ROUTE_KEYWORDS as $keyword) {
            if (str_contains($target, $keyword)) {
                $looksLikeAuthEndpoint = true;

                break;
            }
        }

        if (! $looksLikeAuthEndpoint || $this->hasThrottle($route->middleware)) {
            return [];
        }

        return [
            new Violation(
                ruleId: $this->id(),
                title: $this->title(),
                description: "Authentication endpoint '{$route->uri}' returned 401 without rate limiting; repeated attempts may enable brute-force abuse.",
                severity: $this->severity(),
                route: $route->uri,
                context: [
                    'status_code' => $response->statusCode,
                    'method' => strtoupper($request->method),
                    'middleware' => $route->middleware,
                    'is_authenticated' => $context->auth !== null ? $context->auth->isAuthenticated : false,
                ],
            ),
        ];
    }

    /**
     * @param list<string> $middleware
     */
    private function hasThrottle(array $middleware): bool
    {
        foreach ($middleware as $item) {
            foreach (self::THROTTLE_PREFIXES as $prefix) {
                if (str_starts_with($item, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
