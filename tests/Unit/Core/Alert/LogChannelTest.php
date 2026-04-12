<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Alert;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeShield\Core\Alert\LogChannel;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class LogChannelTest extends TestCase
{
    public function test_channel_name_is_log(): void
    {
        $channel = new LogChannel(true, $this->createStub(LoggerInterface::class));
        $this->assertSame('log', $channel->channelName());
    }

    public function test_is_enabled_reflects_constructor_flag(): void
    {
        $logger = $this->createStub(LoggerInterface::class);
        $this->assertTrue((new LogChannel(true, $logger))->isEnabled());
        $this->assertFalse((new LogChannel(false, $logger))->isEnabled());
    }

    public function test_notify_calls_logger_with_error_level_for_critical(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('error', $this->stringContains('[RuntimeShield]'), $this->anything());

        $channel = new LogChannel(true, $logger);
        $channel->notify($this->makeEvent(Severity::CRITICAL));
    }

    public function test_notify_calls_logger_with_warning_level_for_high(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('warning', $this->anything(), $this->anything());

        $channel = new LogChannel(true, $logger);
        $channel->notify($this->makeEvent(Severity::HIGH));
    }

    public function test_notify_calls_logger_with_notice_level_for_medium(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('notice', $this->anything(), $this->anything());

        (new LogChannel(true, $logger))->notify($this->makeEvent(Severity::MEDIUM));
    }

    public function test_notify_calls_logger_with_info_level_for_low(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->with('info', $this->anything(), $this->anything());

        (new LogChannel(true, $logger))->notify($this->makeEvent(Severity::LOW));
    }

    public function test_log_context_contains_route_and_violations(): void
    {
        $captured = null;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('log')
            ->willReturnCallback(static function (string $level, string $msg, array $ctx) use (&$captured): void {
                $captured = $ctx;
            });

        (new LogChannel(true, $logger))->notify($this->makeEvent(Severity::HIGH, 'api/orders'));

        $this->assertIsArray($captured);
        $this->assertSame('api/orders', $captured['route']);
        $this->assertIsArray($captured['violations']);
    }

    private function makeEvent(Severity $severity, string $route = 'home'): AlertEvent
    {
        return new AlertEvent(
            new ViolationCollection([new Violation('r1', 'Title', 'Desc', $severity, $route)]),
            $route,
            new \DateTimeImmutable(),
        );
    }
}
