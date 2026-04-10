<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Fires when a mutable route (POST / PUT / PATCH) has no input-validation
 * middleware attached, signalling a potential mass-assignment or injection risk.
 *
 * Because Laravel validation is often done inside controllers or Form Requests
 * rather than as middleware, this rule is intentionally LOW severity — it acts
 * as a reminder rather than a hard security block.
 */
final class MissingValidationRule implements RuleContract
{
    /** HTTP methods that accept a request body and should validate it. */
    private const MUTABLE_METHODS = ['POST', 'PUT', 'PATCH'];

    /** Middleware names that imply validation is enforced at the routing layer. */
    private const VALIDATION_PREFIXES = [
        'validate',
        'input-validation',
        'form-request',
    ];

    public function id(): string
    {
        return 'missing-validation';
    }

    public function title(): string
    {
        return 'Missing Input Validation Middleware';
    }

    public function severity(): Severity
    {
        return Severity::LOW;
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
            foreach (self::VALIDATION_PREFIXES as $prefix) {
                if (str_starts_with($middleware, $prefix)) {
                    return [];
                }
            }
        }

        return [
            new Violation(
                ruleId: $this->id(),
                title: $this->title(),
                description: "Route '{$route->uri}' ({$request->method}) has no explicit validation middleware — ensure the handler validates all user input.",
                severity: $this->severity(),
                route: $route->uri,
                context: ['method' => $request->method, 'middleware' => $route->middleware],
            ),
        ];
    }
}
