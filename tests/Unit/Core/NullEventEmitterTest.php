<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Core;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\NullEventEmitter;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;

final class NullEventEmitterTest extends TestCase
{
    // ------------------------------------------------------------------ helpers

    private function makeContext(): SecurityRuntimeContext
    {
        return new SecurityRuntimeContext(
            requestId: 'evt-test-' . uniqid(),
            createdAt: new \DateTimeImmutable(),
        );
    }

    private function makeViolation(): Violation
    {
        return new Violation(
            ruleId: 'test-rule',
            title: 'Test Rule',
            description: 'Test violation',
            severity: Severity::LOW,
        );
    }

    // ------------------------------------------------------------------ beforeScan

    #[Test]
    public function before_scan_does_not_throw(): void
    {
        $emitter = new NullEventEmitter();

        $this->expectNotToPerformAssertions();
        $emitter->beforeScan($this->makeContext(), 5);
    }

    #[Test]
    public function before_scan_with_zero_rules_does_not_throw(): void
    {
        $emitter = new NullEventEmitter();

        $this->expectNotToPerformAssertions();
        $emitter->beforeScan($this->makeContext(), 0);
    }

    // ------------------------------------------------------------------ afterScan

    #[Test]
    public function after_scan_does_not_throw_with_empty_violations(): void
    {
        $emitter = new NullEventEmitter();
        $violations = new ViolationCollection([]);

        $this->expectNotToPerformAssertions();
        $emitter->afterScan($this->makeContext(), $violations, 1.5);
    }

    #[Test]
    public function after_scan_does_not_throw_with_violations(): void
    {
        $emitter = new NullEventEmitter();
        $violations = new ViolationCollection([$this->makeViolation()]);

        $this->expectNotToPerformAssertions();
        $emitter->afterScan($this->makeContext(), $violations, 12.3);
    }

    // ------------------------------------------------------------------ violationDetected

    #[Test]
    public function violation_detected_does_not_throw(): void
    {
        $emitter = new NullEventEmitter();

        $this->expectNotToPerformAssertions();
        $emitter->violationDetected($this->makeViolation(), $this->makeContext());
    }

    // ------------------------------------------------------------------ interface contract

    #[Test]
    public function implements_event_emitter_contract(): void
    {
        $emitter = new NullEventEmitter();

        $this->assertInstanceOf(\RuntimeShield\Contracts\EventEmitterContract::class, $emitter);
    }

    #[Test]
    public function multiple_calls_do_not_accumulate_state(): void
    {
        $emitter = new NullEventEmitter();
        $context = $this->makeContext();
        $violations = new ViolationCollection([]);

        $emitter->beforeScan($context, 3);
        $emitter->beforeScan($context, 3);
        $emitter->afterScan($context, $violations, 1.0);
        $emitter->afterScan($context, $violations, 1.0);

        $this->assertTrue(true);
    }
}
