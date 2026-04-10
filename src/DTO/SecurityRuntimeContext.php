<?php

declare(strict_types=1);

namespace RuntimeShield\DTO;

use DateTimeImmutable;

/**
 * Immutable, fully assembled security context for a single request lifecycle.
 *
 * Aggregates all four signal types (request, response, route, auth) into one
 * cohesive object that the rule engine and any downstream consumers can inspect
 * without touching the raw signal store.
 */
final class SecurityRuntimeContext
{
    public function __construct(
        public readonly string $requestId,
        public readonly DateTimeImmutable $createdAt,
    ) {
    }
}
