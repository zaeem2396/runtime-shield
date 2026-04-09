<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Signal;

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
    ) {}
}
