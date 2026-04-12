<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts;

use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Abstraction over the application's event dispatching mechanism.
 *
 * Decouples the core engine from Laravel's Dispatcher so the engine
 * remains framework-agnostic and unit-testable without bootstrapping
 * the entire IoC container.
 *
 * Two implementations are provided:
 *  - NullEventEmitter — no-op; used in non-Laravel environments or tests
 *  - LaravelEventEmitter — delegates to Illuminate\Contracts\Events\Dispatcher
 */
interface EventEmitterContract
{
    /**
     * Fired before the rule engine starts evaluating a context.
     *
     * @param int $ruleCount The total number of rules that will be evaluated
     */
    public function beforeScan(SecurityRuntimeContext $context, int $ruleCount): void;

    /**
     * Fired after the rule engine finishes evaluating a context.
     *
     * @param float $durationMs Wall-clock time of the evaluation in milliseconds
     */
    public function afterScan(
        SecurityRuntimeContext $context,
        ViolationCollection $violations,
        float $durationMs,
    ): void;

    /**
     * Fired once for each individual violation detected during a scan.
     */
    public function violationDetected(Violation $violation, SecurityRuntimeContext $context): void;
}
