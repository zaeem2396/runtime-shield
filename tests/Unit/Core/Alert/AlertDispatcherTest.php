<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Alert;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Alert\AlertDispatcher;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class AlertDispatcherTest extends TestCase
{
    public function test_channels_empty_by_default(): void
    {
        $dispatcher = new AlertDispatcher(Severity::HIGH);
        $this->assertSame([], $dispatcher->channels());
    }

    public function test_add_channel_returns_static(): void
    {
        $dispatcher = new AlertDispatcher(Severity::HIGH);
        $channel = $this->makeChannel('log');
        $result = $dispatcher->addChannel($channel);
        $this->assertSame($dispatcher, $result);
    }

    public function test_channels_contains_added_channel(): void
    {
        $dispatcher = new AlertDispatcher(Severity::HIGH);
        $channel = $this->makeChannel('log');
        $dispatcher->addChannel($channel);
        $this->assertContains($channel, $dispatcher->channels());
    }

    public function test_dispatch_calls_enabled_channel(): void
    {
        $notified = false;
        $channel = $this->makeChannel('log', true, function () use (&$notified): void {
            $notified = true;
        });

        $dispatcher = new AlertDispatcher(Severity::HIGH);
        $dispatcher->addChannel($channel);
        $dispatcher->dispatch($this->makeViolations([Severity::HIGH]), 'route');

        $this->assertTrue($notified);
    }

    public function test_dispatch_skips_disabled_channel(): void
    {
        $notified = false;
        $channel = $this->makeChannel('log', false, function () use (&$notified): void {
            $notified = true;
        });

        $dispatcher = new AlertDispatcher(Severity::HIGH);
        $dispatcher->addChannel($channel);
        $dispatcher->dispatch($this->makeViolations([Severity::HIGH]), 'route');

        $this->assertFalse($notified);
    }

    public function test_dispatch_filters_below_min_severity(): void
    {
        $received = null;
        $channel = $this->makeChannel('log', true, function (AlertEvent $e) use (&$received): void {
            $received = $e;
        });

        // min severity = HIGH, only LOW violation provided → no dispatch
        $dispatcher = new AlertDispatcher(Severity::HIGH);
        $dispatcher->addChannel($channel);
        $dispatcher->dispatch($this->makeViolations([Severity::LOW]), 'route');

        $this->assertNull($received);
    }

    public function test_dispatch_includes_critical_when_min_is_high(): void
    {
        $received = null;
        $channel = $this->makeChannel('log', true, function (AlertEvent $e) use (&$received): void {
            $received = $e;
        });

        $dispatcher = new AlertDispatcher(Severity::HIGH);
        $dispatcher->addChannel($channel);
        $dispatcher->dispatch($this->makeViolations([Severity::CRITICAL]), 'route');

        $this->assertNotNull($received);
        $this->assertSame(1, $received->violations->count());
    }

    public function test_min_severity_accessor(): void
    {
        $dispatcher = new AlertDispatcher(Severity::CRITICAL);
        $this->assertSame(Severity::CRITICAL, $dispatcher->minSeverity());
    }

    public function test_dispatch_noop_when_violations_empty(): void
    {
        $notified = false;
        $channel = $this->makeChannel('log', true, function () use (&$notified): void {
            $notified = true;
        });

        $dispatcher = new AlertDispatcher(Severity::HIGH);
        $dispatcher->addChannel($channel);
        $dispatcher->dispatch(new ViolationCollection(), 'route');

        $this->assertFalse($notified);
    }

    /** @param list<Severity> $severities */
    private function makeViolations(array $severities): ViolationCollection
    {
        return new ViolationCollection(array_map(
            static fn (Severity $s): Violation => new Violation('rule-id', 'Title', 'Desc', $s, 'route'),
            $severities,
        ));
    }

    private function makeChannel(
        string $name,
        bool $enabled = true,
        ?\Closure $onNotify = null,
    ): \RuntimeShield\Contracts\Alert\AlertChannelContract {
        return new class ($name, $enabled, $onNotify) implements \RuntimeShield\Contracts\Alert\AlertChannelContract {
            public function __construct(
                private readonly string $name,
                private readonly bool $enabled,
                private readonly ?\Closure $onNotify,
            ) {
            }

            public function channelName(): string
            {
                return $this->name;
            }

            public function isEnabled(): bool
            {
                return $this->enabled;
            }

            public function notify(AlertEvent $event): void
            {
                if ($this->onNotify !== null) {
                    ($this->onNotify)($event);
                }
            }
        };
    }
}
