<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Performance;

use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\Laravel\Jobs\EvaluationJob;

/**
 * Decorator over RuleEngineContract that optionally dispatches rule evaluation
 * to the Laravel queue instead of running it synchronously.
 *
 * Behaviour:
 *   async = true  → dispatches EvaluationJob and returns an empty collection
 *                   immediately; violations are handled on the queue worker.
 *   async = false → delegates synchronously to the inner RuleEngine (default).
 *
 * Controlled by runtime_shield.performance.async in the config file.
 */
final class AsyncRuleEngine implements RuleEngineContract
{
    public function __construct(
        private readonly RuleEngineContract $inner,
        private readonly bool $async,
    ) {
    }

    public function run(SecurityRuntimeContext $context): ViolationCollection
    {
        if ($this->async) {
            EvaluationJob::dispatch($context);

            return new ViolationCollection();
        }

        return $this->inner->run($context);
    }

    /** Whether async dispatch is currently active. */
    public function isAsync(): bool
    {
        return $this->async;
    }
}
