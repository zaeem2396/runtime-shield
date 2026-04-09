<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Signal;

/**
 * Immutable snapshot of an inbound HTTP request at capture time.
 */
final class RequestSignal
{
    public function __construct(
        public readonly string $method,
        public readonly string $url,
        public readonly string $path,
        public readonly string $ip,
    ) {}
}
