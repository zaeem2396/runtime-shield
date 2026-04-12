<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Events;

use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Fired immediately before the rule engine evaluates a SecurityRuntimeContext.
 *
 * Listeners may use this event to:
 *  - Log or trace the start of a scan cycle
 *  - Inject additional context or metadata before evaluation
 *  - Implement custom pre-scan guards or circuit breakers
 *
 * @see AfterScanEvent  — fired after evaluation completes
 */
final class BeforeScanEvent
{
    public function __construct(
        /** The runtime context that is about to be evaluated. */
        public readonly SecurityRuntimeContext $context,
        /** Number of rules that will be evaluated. */
        public readonly int $ruleCount,
        /** The exact moment this event was fired. */
        public readonly \DateTimeImmutable $startedAt,
    ) {
    }
}
