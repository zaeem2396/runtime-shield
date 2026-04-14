<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Detect anomalous response behavior often correlated with instability or abuse.
 */
final class ResponseAnomalyRule implements RuleContract
{
    private const SLOW_RESPONSE_MS = 5_000.0;
    private const LARGE_BODY_BYTES = 2_000_000;

    public function id(): string
    {
        return 'response-anomaly-detected';
    }

    public function title(): string
    {
        return 'Response Anomaly Detected';
    }

    public function severity(): Severity
    {
        return Severity::MEDIUM;
    }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        $response = $context->response;
        $request = $context->request;

        if ($response === null) {
            return [];
        }

        $anomalies = [];

        if ($response->responseTimeMs >= self::SLOW_RESPONSE_MS) {
            $anomalies[] = sprintf('slow response (%.2f ms)', $response->responseTimeMs);
        }

        if ($response->bodySize >= self::LARGE_BODY_BYTES) {
            $anomalies[] = sprintf('large body (%d bytes)', $response->bodySize);
        }

        if ($response->statusCode >= 500 && $response->bodySize === 0) {
            $anomalies[] = '5xx response with empty body';
        }

        if ($request !== null && strtoupper($request->method) !== 'HEAD' && $response->statusCode === 204 && $response->bodySize > 0) {
            $anomalies[] = '204 response contains a non-empty body';
        }

        if ($anomalies === []) {
            return [];
        }

        $routeSignal = $context->route;
        $route = $routeSignal !== null
            ? $routeSignal->uri
            : ($request !== null ? $request->path : '');

        return [
            new Violation(
                ruleId: $this->id(),
                title: $this->title(),
                description: "Response for '{$route}' shows anomalous behavior: " . implode('; ', $anomalies) . '.',
                severity: $this->severity(),
                route: $route,
                context: [
                    'status_code' => $response->statusCode,
                    'response_time_ms' => $response->responseTimeMs,
                    'body_size' => $response->bodySize,
                    'anomalies' => $anomalies,
                ],
            ),
        ];
    }
}
