<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Rule;

use RuntimeShield\Contracts\Rule\RuleEngineContract;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Core implementation of the Rule Engine.
 *
 * Iterates every rule registered in the RuleRegistry, runs them against the
 * supplied context, and aggregates all violations into a ViolationCollection.
 */
final class RuleEngine implements RuleEngineContract
{
    public function __construct(private readonly RuleRegistry $registry)
    {
    }

    public function run(SecurityRuntimeContext $context): ViolationCollection
    {
        $violations = [];

        foreach ($this->registry->all() as $rule) {
            foreach ($rule->evaluate($context) as $violation) {
                $violations[] = $violation;
            }
        }

        return new ViolationCollection($violations);
    }
}
