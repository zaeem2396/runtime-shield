<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Http;

/**
 * Minimal HTTP response record for internal transport (OpenAI, webhooks, etc.).
 */
final class HttpResponse
{
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
    ) {
    }

    public function isSuccess(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
