<?php

declare(strict_types=1);

namespace RuntimeShield\Engine;

use RuntimeShield\Contracts\EngineContract;
use RuntimeShield\Contracts\EventEmitterContract;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\NullEventEmitter;
use RuntimeShield\Core\Rule\RuleRegistry;
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
 * - v0.9.0+: lifecycle event hooks (BeforeScan, AfterScan, ViolationDetected)
 */
final class RuntimeShieldEngine implements EngineContract
{
    private bool $booted = false;

    public function __construct(
        private readonly RuntimeShieldManager $manager,
        private readonly RuleEngineContract $ruleEngine,
        private readonly RuleRegistry $registry = new RuleRegistry(),
        private readonly EventEmitterContract $emitter = new NullEventEmitter(),
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
     * Fires BeforeScanEvent before evaluation and AfterScanEvent after.
     * For each individual violation, ViolationDetectedEvent is also fired.
     *
     * This is the primary entry-point for on-demand rule evaluation; the CLI
     * scanner and any event listeners can call this directly without touching
     * the middleware pipeline.
     */
    public function evaluate(SecurityRuntimeContext $context): ViolationCollection
    {
        $ruleCount = $this->registry->count();
        $this->emitter->beforeScan($context, $ruleCount);

        $start = hrtime(true);
        $violations = $this->ruleEngine->run($context);
        $durationMs = (hrtime(true) - $start) / 1_000_000;

        foreach ($violations->all() as $violation) {
            $this->emitter->violationDetected($violation, $context);
        }

        $this->emitter->afterScan($context, $violations, $durationMs);

        return $violations;
    }
}
