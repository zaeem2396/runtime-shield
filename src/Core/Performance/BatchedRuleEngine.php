<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Performance;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\Core\Rule\RuleRegistry;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Rule engine that processes rules in configurable batches and enforces an
 * evaluation timeout to cap worst-case middleware latency.
 *
 * Rules are taken from the RuleRegistry, split into chunks of $batchSize,
 * and evaluated one batch at a time. If the cumulative elapsed time exceeds
 * $timeoutMs the engine stops processing further batches and returns whatever
 * violations it has collected so far.
 *
 * Controlled by runtime_shield.performance.batch_size and .timeout_ms.
 */
final class BatchedRuleEngine implements RuleEngineContract
{
    public function __construct(
        private readonly RuleRegistry $registry,
        private readonly int $batchSize,
        private readonly int $timeoutMs,
    ) {
    }

    public function run(SecurityRuntimeContext $context): ViolationCollection
    {
        if ($this->registry->count() === 0) {
            return new ViolationCollection();
        }

        $rules      = $this->registry->all();
        $batches    = array_chunk($rules, max(1, $this->batchSize));
        $violations = [];
        $startNs    = hrtime(true);
        $limitNs    = $this->timeoutMs * 1_000_000;

        foreach ($batches as $batch) {
            if ($this->timeoutMs > 0 && (hrtime(true) - $startNs) >= $limitNs) {
                break;
            }

            foreach ($batch as $rule) {
                /** @var RuleContract $rule */
                foreach ($rule->evaluate($context) as $violation) {
                    $violations[] = $violation;
                }
            }
        }

        return new ViolationCollection($violations);
    }

    public function batchSize(): int
    {
        return $this->batchSize;
    }

    public function timeoutMs(): int
    {
        return $this->timeoutMs;
    }
}
