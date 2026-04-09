<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Signal;

use RuntimeShield\DTO\Signal\RequestSignal;

/**
 * Framework-agnostic converter from raw data maps to typed Signal DTOs.
 *
 * Use this when you have array data from a non-Laravel source (queues,
 * webhooks, test fixtures) and need to construct a Signal without a
 * live HTTP Request object.
 */
final class SignalNormalizer
{
    /**
     * Convert a raw key-value map into a RequestSignal.
     *
     * @param array<string, mixed> $data
     */
    public function normalizeRequest(array $data): RequestSignal
    {
        return RequestSignal::fromArray($data);
    }
}
