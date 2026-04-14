<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Detects missing baseline security headers on HTTP responses.
 */
final class MissingSecurityHeadersRule implements RuleContract
{
    /** @var array<string, string> */
    private const REQUIRED_HEADERS = [
        'content-security-policy' => 'Content-Security-Policy',
        'x-frame-options' => 'X-Frame-Options',
    ];

    public function id(): string
    {
        return 'missing-security-headers';
    }

    public function title(): string
    {
        return 'Missing Security Headers';
    }

    public function severity(): Severity
    {
        return Severity::MEDIUM;
    }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        $response = $context->response;

        if ($response === null) {
            return [];
        }

        $headers = $this->normalizeHeaderMap($response->headers);
        $missing = [];

        foreach (self::REQUIRED_HEADERS as $name => $label) {
            if (! isset($headers[$name]) || trim($headers[$name]) === '') {
                $missing[] = $label;
            }
        }

        $request = $context->request;
        $isHttps = $request !== null && str_starts_with(strtolower($request->url), 'https://');

        if ($isHttps && (! isset($headers['strict-transport-security']) || trim($headers['strict-transport-security']) === '')) {
            $missing[] = 'Strict-Transport-Security';
        }

        if ($missing === []) {
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
                description: "Response for '{$route}' is missing recommended security header(s): " . implode(', ', $missing) . '.',
                severity: $this->severity(),
                route: $route,
                context: [
                    'status_code' => $response->statusCode,
                    'missing_headers' => $missing,
                ],
            ),
        ];
    }

    /**
     * @param array<string, mixed> $headers
     *
     * @return array<string, string>
     */
    private function normalizeHeaderMap(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            $normalized[strtolower($name)] = is_string($value) ? $value : (string) json_encode($value);
        }

        return $normalized;
    }
}
