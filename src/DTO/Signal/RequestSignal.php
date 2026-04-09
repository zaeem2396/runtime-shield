<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Signal;

use DateTimeImmutable;

/**
 * Immutable snapshot of an inbound HTTP request at capture time.
 */
final class RequestSignal
{
    /**
     * @param array<string, string> $headers Normalized header map (name → joined value)
     * @param array<string, mixed>  $query   Decoded query string parameters
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly string $path,
        public readonly string $ip,
        public readonly array $headers,
        public readonly array $query,
        public readonly int $bodySize,
        public readonly DateTimeImmutable $capturedAt,
    ) {}

    /**
     * Construct from a raw data map (e.g. from a framework-agnostic normalizer).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $capturedAt = $data['captured_at'] ?? null;
        $headers    = $data['headers'] ?? [];
        $query      = $data['query'] ?? [];

        return new self(
            method: strtoupper((string) ($data['method'] ?? 'GET')),
            url: (string) ($data['url'] ?? ''),
            path: (string) ($data['path'] ?? '/'),
            ip: (string) ($data['ip'] ?? ''),
            headers: is_array($headers) ? $headers : [],
            query: is_array($query) ? $query : [],
            bodySize: (int) ($data['body_size'] ?? 0),
            capturedAt: $capturedAt instanceof DateTimeImmutable
                ? $capturedAt
                : new DateTimeImmutable(),
        );
    }
}
