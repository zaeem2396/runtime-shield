<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use RuntimeShield\Contracts\EventEmitterContract;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\Laravel\Events\AfterScanEvent;
use RuntimeShield\Laravel\Events\BeforeScanEvent;
use RuntimeShield\Laravel\Events\ViolationDetectedEvent;

/**
 * EventEmitter implementation that delegates to Laravel's event Dispatcher.
 *
 * Fires BeforeScanEvent, AfterScanEvent, and ViolationDetectedEvent so that
 * application listeners can react to the scan lifecycle without coupling
 * their code to RuntimeShield internals.
 */
final class LaravelEventEmitter implements EventEmitterContract
{
    public function __construct(private readonly Dispatcher $dispatcher) {}

    public function beforeScan(SecurityRuntimeContext $context, int $ruleCount): void
    {
        $this->dispatcher->dispatch(new BeforeScanEvent(
            context: $context,
            ruleCount: $ruleCount,
            startedAt: new \DateTimeImmutable(),
        ));
    }

    public function afterScan(
        SecurityRuntimeContext $context,
        ViolationCollection $violations,
        float $durationMs,
    ): void {
        $this->dispatcher->dispatch(new AfterScanEvent(
            context: $context,
            violations: $violations,
            durationMs: $durationMs,
        ));
    }

    public function violationDetected(Violation $violation, SecurityRuntimeContext $context): void
    {
        $this->dispatcher->dispatch(new ViolationDetectedEvent(
            violation: $violation,
            context: $context,
            detectedAt: new \DateTimeImmutable(),
        ));
    }
}
