<?php

declare(strict_types=1);

namespace RuntimeShield\Rules;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Detect likely server-side exception leakage in HTTP responses.
 */
final class ErrorExposureRule implements RuleContract
{
    /** @var list<string> */
    private const DEBUG_HEADERS = [
        'x-debug-exception',
        'x-debug-exception-file',
        'x-debug-token',
        'x-debug-token-link',
    ];

    public function id(): string
    {
        return 'error-exposure-detected';
    }

    public function title(): string
    {
        return 'Error Exposure Detected';
    }

    public function severity(): Severity
    {
        return Severity::HIGH;
    }

    public function evaluate(SecurityRuntimeContext $context): array
    {
        $response = $context->response;
        $request = $context->request;

        if ($response === null || $response->statusCode < 500) {
            return [];
        }

        $headers = $this->normalizeHeaderMap($response->headers);
        $debugHeaders = [];

        foreach (self::DEBUG_HEADERS as $header) {
            if (isset($headers[$header]) && $headers[$header] !== '') {
                $debugHeaders[] = $header;
            }
        }

        $contentType = $headers['content-type'] ?? '';
        $htmlError = str_contains(strtolower($contentType), 'text/html') && $response->bodySize > 0;

        if ($debugHeaders === [] && ! $htmlError) {
            return [];
        }

        $routeSignal = $context->route;
        $route = $routeSignal !== null
            ? $routeSignal->uri
            : ($request !== null ? $request->path : '');
        $reasons = [];

        if ($debugHeaders !== []) {
            $reasons[] = 'debug headers present';
        }

        if ($htmlError) {
            $reasons[] = 'HTML body returned for 5xx response';
        }

        return [
            new Violation(
                ruleId: $this->id(),
                title: $this->title(),
                description: "Response for '{$route}' appears to expose internal error details (" . implode('; ', $reasons) . ').',
                severity: $this->severity(),
                route: $route,
                context: [
                    'status_code' => $response->statusCode,
                    'status_text' => $response->statusText,
                    'debug_headers' => $debugHeaders,
                    'content_type' => $contentType,
                    'body_size' => $response->bodySize,
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
