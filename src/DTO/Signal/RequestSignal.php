<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Signal;

/**
 * Immutable snapshot of an inbound HTTP request at capture time.
 */
final class RequestSignal
{
    /**
     * @param array<string, mixed> $headers Normalized header map (name → value)
     * @param array<string, mixed> $query Decoded query string parameters
     */
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly string $path,
        public readonly string $ip,
        public readonly array $headers,
        public readonly array $query,
        public readonly int $bodySize,
        public readonly \DateTimeImmutable $capturedAt,
    ) {
    }

    /**
     * Construct from a raw data map (e.g. from a framework-agnostic normalizer).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $capturedAt = $data['captured_at'] ?? null;
        $headers = $data['headers'] ?? [];
        $query = $data['query'] ?? [];
        $method = $data['method'] ?? null;
        $url = $data['url'] ?? null;
        $path = $data['path'] ?? null;
        $ip = $data['ip'] ?? null;
        $bodySize = $data['body_size'] ?? null;

        return new self(
            method: is_string($method) ? strtoupper($method) : 'GET',
            url: is_string($url) ? $url : '',
            path: is_string($path) ? $path : '/',
            ip: is_string($ip) ? $ip : '',
            headers: is_array($headers) ? $headers : [],
            query: is_array($query) ? $query : [],
            bodySize: is_numeric($bodySize) ? (int) $bodySize : 0,
            capturedAt: $capturedAt instanceof \DateTimeImmutable
                ? $capturedAt
                : new \DateTimeImmutable(),
        );
    }
}
