<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Alert;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\Contracts\Alert\AlertDispatcherContract;
use RuntimeShield\Core\Alert\AlertThrottle;
use RuntimeShield\Core\Alert\ThrottledAlertDispatcher;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class ThrottledAlertDispatcherTest extends TestCase
{
    public function test_dispatches_when_no_violations_are_throttled(): void
    {
        $dispatched = false;
        $inner = $this->makeInner(function () use (&$dispatched): void {
            $dispatched = true;
        });

        $throttle = new AlertThrottle(300);
        $dispatcher = new ThrottledAlertDispatcher($inner, $throttle);
        $dispatcher->dispatch($this->makeViolations(['rule-a']), 'route');

        $this->assertTrue($dispatched);
    }

    public function test_skips_dispatch_when_all_violations_are_throttled(): void
    {
        $dispatched = false;
        $inner = $this->makeInner(function () use (&$dispatched): void {
            $dispatched = true;
        });

        $throttle = new AlertThrottle(300);
        $throttle->record('rule-a'); // pre-throttle

        $dispatcher = new ThrottledAlertDispatcher($inner, $throttle);
        $dispatcher->dispatch($this->makeViolations(['rule-a']), 'route');

        $this->assertFalse($dispatched);
    }

    public function test_partial_throttle_dispatches_only_unthrottled(): void
    {
        $receivedCount = 0;
        $inner = $this->makeInner(function (ViolationCollection $v) use (&$receivedCount): void {
            $receivedCount = $v->count();
        });

        $throttle = new AlertThrottle(300);
        $throttle->record('rule-a'); // throttled

        $dispatcher = new ThrottledAlertDispatcher($inner, $throttle);
        $dispatcher->dispatch($this->makeViolations(['rule-a', 'rule-b']), 'route');

        $this->assertSame(1, $receivedCount); // only rule-b dispatched
    }

    public function test_records_violation_after_dispatch(): void
    {
        $inner = $this->makeInner();
        $throttle = new AlertThrottle(300);
        $dispatcher = new ThrottledAlertDispatcher($inner, $throttle);

        $this->assertFalse($throttle->isThrottled('rule-a'));
        $dispatcher->dispatch($this->makeViolations(['rule-a']), 'route');
        $this->assertTrue($throttle->isThrottled('rule-a'));
    }

    public function test_throttle_accessor(): void
    {
        $throttle = new AlertThrottle(60);
        $dispatcher = new ThrottledAlertDispatcher($this->makeInner(), $throttle);
        $this->assertSame($throttle, $dispatcher->throttle());
    }

    public function test_add_channel_delegates_to_inner(): void
    {
        $inner = $this->makeInner();
        $dispatcher = new ThrottledAlertDispatcher($inner, new AlertThrottle(300));
        $channel = $this->makeNullChannel();
        $result = $dispatcher->addChannel($channel);
        $this->assertSame($dispatcher, $result);
    }

    public function test_channels_delegates_to_inner(): void
    {
        $inner = $this->makeInner();
        $dispatcher = new ThrottledAlertDispatcher($inner, new AlertThrottle(300));
        $this->assertSame([], $dispatcher->channels());
    }

    /** @param list<string> $ruleIds */
    private function makeViolations(array $ruleIds): ViolationCollection
    {
        return new ViolationCollection(array_map(
            static fn (string $id): Violation => new Violation($id, 'Title', 'Desc', Severity::HIGH, 'r'),
            $ruleIds,
        ));
    }

    private function makeInner(?\Closure $onDispatch = null): AlertDispatcherContract
    {
        return new class ($onDispatch) implements AlertDispatcherContract {
            public function __construct(private readonly ?\Closure $onDispatch)
            {
            }

            public function dispatch(ViolationCollection $violations, string $route = ''): void
            {
                if ($this->onDispatch !== null) {
                    ($this->onDispatch)($violations);
                }
            }

            public function addChannel(AlertChannelContract $channel): static
            {
                return $this;
            }

            public function channels(): array
            {
                return [];
            }
        };
    }

    private function makeNullChannel(): AlertChannelContract
    {
        return new class () implements AlertChannelContract {
            public function channelName(): string
            {
                return 'null';
            }

            public function isEnabled(): bool
            {
                return false;
            }

            public function notify(AlertEvent $event): void
            {
            }
        };
    }
}
