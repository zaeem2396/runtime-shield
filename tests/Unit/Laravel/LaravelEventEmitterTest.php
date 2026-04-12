<?php

declare(strict_types=1);

namespace RuntimeShield\Tests\Unit\Laravel;

use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;
use RuntimeShield\DTO\SecurityRuntimeContext;
use RuntimeShield\Laravel\Events\AfterScanEvent;
use RuntimeShield\Laravel\Events\BeforeScanEvent;
use RuntimeShield\Laravel\Events\ViolationDetectedEvent;
use RuntimeShield\Laravel\LaravelEventEmitter;

final class LaravelEventEmitterTest extends TestCase
{
    // ------------------------------------------------------------------ helpers

    private function makeContext(): SecurityRuntimeContext
    {
        return new SecurityRuntimeContext(
            requestId: 'emitter-test-' . uniqid(),
            createdAt: new \DateTimeImmutable(),
        );
    }

    private function makeViolation(): Violation
    {
        return new Violation(
            ruleId: 'test-rule',
            title: 'Test Rule',
            description: 'Test violation',
            severity: Severity::HIGH,
            route: '/test',
        );
    }

    /**
     * @return array{LaravelEventEmitter, object{dispatched: list<object>}}
     */
    private function makeEmitterWithCapture(): array
    {
        $capture = new class {
            /** @var list<object> */
            public array $dispatched = [];
        };

        $dispatcher = $this->createMock(Dispatcher::class);
        $dispatcher->method('dispatch')
            ->willReturnCallback(static function (object $event) use ($capture): void {
                $capture->dispatched[] = $event;
            });

        $emitter = new LaravelEventEmitter($dispatcher);

        return [$emitter, $capture];
    }

    // ------------------------------------------------------------------ beforeScan

    #[Test]
    public function before_scan_dispatches_before_scan_event(): void
    {
        [$emitter, $capture] = $this->makeEmitterWithCapture();
        $context = $this->makeContext();

        $emitter->beforeScan($context, 7);

        $this->assertCount(1, $capture->dispatched);
        $event = $capture->dispatched[0];
        $this->assertInstanceOf(BeforeScanEvent::class, $event);
        $this->assertSame($context, $event->context);
        $this->assertSame(7, $event->ruleCount);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->startedAt);
    }

    // ------------------------------------------------------------------ afterScan

    #[Test]
    public function after_scan_dispatches_after_scan_event(): void
    {
        [$emitter, $capture] = $this->makeEmitterWithCapture();
        $context = $this->makeContext();
        $violations = new ViolationCollection([$this->makeViolation()]);

        $emitter->afterScan($context, $violations, 4.2);

        $this->assertCount(1, $capture->dispatched);
        $event = $capture->dispatched[0];
        $this->assertInstanceOf(AfterScanEvent::class, $event);
        $this->assertSame($context, $event->context);
        $this->assertSame($violations, $event->violations);
        $this->assertSame(4.2, $event->durationMs);
    }

    // ------------------------------------------------------------------ violationDetected

    #[Test]
    public function violation_detected_dispatches_violation_detected_event(): void
    {
        [$emitter, $capture] = $this->makeEmitterWithCapture();
        $context = $this->makeContext();
        $violation = $this->makeViolation();

        $emitter->violationDetected($violation, $context);

        $this->assertCount(1, $capture->dispatched);
        $event = $capture->dispatched[0];
        $this->assertInstanceOf(ViolationDetectedEvent::class, $event);
        $this->assertSame($violation, $event->violation);
        $this->assertSame($context, $event->context);
        $this->assertInstanceOf(\DateTimeImmutable::class, $event->detectedAt);
    }

    // ------------------------------------------------------------------ multiple events

    #[Test]
    public function all_three_event_types_are_dispatched_independently(): void
    {
        [$emitter, $capture] = $this->makeEmitterWithCapture();
        $context = $this->makeContext();
        $violation = $this->makeViolation();
        $violations = new ViolationCollection([$violation]);

        $emitter->beforeScan($context, 3);
        $emitter->violationDetected($violation, $context);
        $emitter->afterScan($context, $violations, 2.0);

        $this->assertCount(3, $capture->dispatched);
        $this->assertInstanceOf(BeforeScanEvent::class, $capture->dispatched[0]);
        $this->assertInstanceOf(ViolationDetectedEvent::class, $capture->dispatched[1]);
        $this->assertInstanceOf(AfterScanEvent::class, $capture->dispatched[2]);
    }

    // ------------------------------------------------------------------ contract

    #[Test]
    public function implements_event_emitter_contract(): void
    {
        $dispatcher = $this->createMock(Dispatcher::class);
        $emitter = new LaravelEventEmitter($dispatcher);

        $this->assertInstanceOf(\RuntimeShield\Contracts\EventEmitterContract::class, $emitter);
    }
}
