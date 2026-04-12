<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Alert;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Alert\NullAlertChannel;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class NullAlertChannelTest extends TestCase
{
    private NullAlertChannel $channel;

    public function test_channel_name_is_null(): void
    {
        $this->assertSame('null', $this->channel->channelName());
    }

    public function test_is_enabled_always_returns_false(): void
    {
        $this->assertFalse($this->channel->isEnabled());
    }

    public function test_notify_does_not_throw(): void
    {
        $this->channel->notify($this->makeEvent());
        $this->addToAssertionCount(1);
    }

    public function test_notify_can_be_called_multiple_times(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->channel->notify($this->makeEvent());
        }

        $this->addToAssertionCount(1);
    }

    protected function setUp(): void
    {
        $this->channel = new NullAlertChannel();
    }

    private function makeEvent(): AlertEvent
    {
        $violations = new ViolationCollection([
            new Violation('r1', 'Title', 'Desc', Severity::HIGH, 'route'),
        ]);

        return new AlertEvent($violations, 'home', new \DateTimeImmutable());
    }
}
