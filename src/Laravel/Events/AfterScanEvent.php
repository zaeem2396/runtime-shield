<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Events;

use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Fired immediately after the rule engine finishes evaluating a context.
 *
 * Listeners may use this event to:
 *  - Persist or aggregate violation summaries
 *  - Trigger custom alerting logic outside the built-in channels
 *  - Record performance metrics for the scan cycle
 *
 * @see BeforeScanEvent — fired before evaluation starts
 */
final class AfterScanEvent
{
    public function __construct(
        /** The runtime context that was evaluated. */
        public readonly SecurityRuntimeContext $context,
        /** All violations detected during this scan cycle. */
        public readonly ViolationCollection $violations,
        /** Wall-clock duration of the evaluation in milliseconds. */
        public readonly float $durationMs,
    ) {
    }

    /**
     * Convenience helper — true when the scan detected at least one violation.
     */
    public function hasViolations(): bool
    {
        return $this->violations->count() > 0;
    }
}
