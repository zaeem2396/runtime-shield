<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Signal;

use DateTimeImmutable;

/**
 * Immutable snapshot of an outbound HTTP response at capture time.
 */
final class ResponseSignal
{
    /**
     * @param array<string, string> $headers Normalized response header map
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $statusText,
        public readonly array $headers,
        public readonly int $bodySize,
        public readonly float $responseTimeMs,
        public readonly DateTimeImmutable $capturedAt,
    ) {}

    /**
     * Construct from a raw data map (e.g. from a framework-agnostic normalizer).
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $capturedAt    = $data['captured_at'] ?? null;
        $headers       = $data['headers'] ?? [];
        $responseTimeMs = $data['response_time_ms'] ?? 0.0;

        return new self(
            statusCode: (int) ($data['status_code'] ?? 200),
            statusText: (string) ($data['status_text'] ?? ''),
            headers: is_array($headers) ? $headers : [],
            bodySize: (int) ($data['body_size'] ?? 0),
            responseTimeMs: is_numeric($responseTimeMs) ? (float) $responseTimeMs : 0.0,
            capturedAt: $capturedAt instanceof DateTimeImmutable
                ? $capturedAt
                : new DateTimeImmutable(),
        );
    }
}
