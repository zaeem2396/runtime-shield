<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Signal;

use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Per-request store for the fully assembled SecurityRuntimeContext.
 * Reset between requests in long-running processes (e.g. Octane workers).
 */
interface RuntimeContextStoreContract
{
    public function store(SecurityRuntimeContext $context): void;

    public function get(): SecurityRuntimeContext|null;

    public function has(): bool;

    public function reset(): void;
}
