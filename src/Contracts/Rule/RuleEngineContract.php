<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Rule;

use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Contract for the Rule Engine.
 *
 * The engine runs all registered rules against a context and aggregates
 * the resulting violations into a ViolationCollection.
 */
interface RuleEngineContract
{
    /**
     * Run all rules against the given context and return every violation found.
     */
    public function run(SecurityRuntimeContext $context): ViolationCollection;
}
