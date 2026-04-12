<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Events;

use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Fired once for every individual Violation detected during a scan cycle.
 *
 * Listeners may use this event to:
 *  - Implement fine-grained per-rule reactions (block IPs, revoke tokens, etc.)
 *  - Stream violations to an external monitoring service in real time
 *  - Record per-violation audit trail entries
 *
 * For bulk reactions, prefer AfterScanEvent which carries the full
 * ViolationCollection after all rules have been evaluated.
 *
 * @see AfterScanEvent — fired once with all violations after evaluation
 */
final class ViolationDetectedEvent
{
    public function __construct(
        /** The individual violation that was detected. */
        public readonly Violation $violation,
        /** The context in which the violation occurred. */
        public readonly SecurityRuntimeContext $context,
        /** Timestamp of when the violation was detected. */
        public readonly \DateTimeImmutable $detectedAt,
    ) {
    }
}
