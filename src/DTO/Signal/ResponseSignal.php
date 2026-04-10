<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Signal;

/**
 * Immutable snapshot of an outbound HTTP response at capture time.
 */
final class ResponseSignal
{
    /**
     * @param array<string, mixed> $headers Normalized response header map
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $statusText,
        public readonly array $headers,
        public readonly int $bodySize,
        public readonly float $responseTimeMs,
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
        $responseTimeMs = $data['response_time_ms'] ?? null;
        $statusCode = $data['status_code'] ?? null;
        $statusText = $data['status_text'] ?? null;
        $bodySize = $data['body_size'] ?? null;

        return new self(
            statusCode: is_numeric($statusCode) ? (int) $statusCode : 200,
            statusText: is_string($statusText) ? $statusText : '',
            headers: is_array($headers) ? $headers : [],
            bodySize: is_numeric($bodySize) ? (int) $bodySize : 0,
            responseTimeMs: is_numeric($responseTimeMs) ? (float) $responseTimeMs : 0.0,
            capturedAt: $capturedAt instanceof \DateTimeImmutable
                ? $capturedAt
                : new \DateTimeImmutable(),
        );
    }
}
