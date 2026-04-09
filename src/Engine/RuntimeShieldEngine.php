<?php

declare(strict_types=1);

namespace RuntimeShield\Engine;

use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Core\RuntimeShieldManager;

/**
 * Central evaluation engine for the request lifecycle.
 *
 * Responsibilities (growing with each version):
 * - v0.1.0: boot lifecycle, idempotency guard, enabled check
 * - v0.4.0+: rule evaluation, violation collection
 * - v0.7.0+: async / batched rule execution
 */
final class RuntimeShieldEngine implements EngineContract
{
    private bool $booted = false;

    public function __construct(
        private readonly RuntimeShieldManager $manager,
    ) {
    }

    /**
     * Boot the engine for the current request.
     *
     * - Idempotent: calling boot() more than once is a no-op.
     * - Fast-exits immediately when the shield is disabled.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        if (! $this->manager->isEnabled()) {
            return;
        }

        $this->booted = true;
    }

    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Reset boot state — intended for test teardown or long-running processes
     * where each "request" should start fresh.
     */
    public function reset(): void
    {
        $this->booted = false;
    }
}
