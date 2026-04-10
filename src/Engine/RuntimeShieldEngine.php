<?php

declare(strict_types=1);

namespace RuntimeShield\Engine;

use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\RuntimeShieldManager;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

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
        private readonly RuleEngineContract $ruleEngine,
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

    /**
     * Run all registered security rules against the given context and return
     * a collection of any violations found.
     *
     * This is the primary entry-point for on-demand rule evaluation; the CLI
     * scanner and any event listeners can call this directly without touching
     * the middleware pipeline.
     */
    public function evaluate(SecurityRuntimeContext $context): ViolationCollection
    {
        return $this->ruleEngine->run($context);
    }
}
