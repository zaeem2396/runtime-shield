<?php

declare(strict_types=1);

namespace RuntimeShield\Core;

use RuntimeShield\Contracts\EventEmitterContract;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * No-op event emitter for use in non-Laravel environments, unit tests,
 * or when event dispatching is explicitly disabled.
 *
 * All methods are intentional no-ops and impose zero allocation overhead.
 */
final class NullEventEmitter implements EventEmitterContract
{
    public function beforeScan(SecurityRuntimeContext $context, int $ruleCount): void
    {
        // Intentional no-op.
    }

    public function afterScan(
        SecurityRuntimeContext $context,
        ViolationCollection $violations,
        float $durationMs,
    ): void {
        // Intentional no-op.
    }

    public function violationDetected(Violation $violation, SecurityRuntimeContext $context): void
    {
        // Intentional no-op.
    }
}
