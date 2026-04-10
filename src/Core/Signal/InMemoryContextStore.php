<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Signal;

use RuntimeShield\Contracts\Signal\RuntimeContextStoreContract;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * In-memory implementation of RuntimeContextStoreContract.
 * Holds at most one SecurityRuntimeContext per request lifecycle.
 */
final class InMemoryContextStore implements RuntimeContextStoreContract
{
    private SecurityRuntimeContext|null $context = null;

    public function store(SecurityRuntimeContext $context): void
    {
        $this->context = $context;
    }

    public function get(): SecurityRuntimeContext|null
    {
        return $this->context;
    }

    public function has(): bool
    {
        return $this->context !== null;
    }

    public function reset(): void
    {
        $this->context = null;
    }
}
