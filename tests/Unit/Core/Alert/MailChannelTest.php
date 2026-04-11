<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Alert;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Alert\MailChannel;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class MailChannelTest extends TestCase
{
    public function test_channel_name_is_mail(): void
    {
        $channel = new MailChannel(true, ['a@example.com'], 'noreply@example.com');
        $this->assertSame('mail', $channel->channelName());
    }

    public function test_is_enabled_true_when_recipients_set(): void
    {
        $this->assertTrue((new MailChannel(true, ['a@example.com'], ''))->isEnabled());
    }

    public function test_is_enabled_false_when_no_recipients(): void
    {
        $this->assertFalse((new MailChannel(true, [], ''))->isEnabled());
    }

    public function test_is_enabled_false_when_disabled_flag(): void
    {
        $this->assertFalse((new MailChannel(false, ['a@example.com'], ''))->isEnabled());
    }

    public function test_notify_calls_send_closure(): void
    {
        $called = false;
        $send = static function () use (&$called): void {
            $called = true;
        };

        $channel = new MailChannel(true, ['a@example.com'], 'noreply@example.com', $send);
        $channel->notify($this->makeEvent());

        $this->assertTrue($called);
    }

    public function test_notify_passes_correct_subject(): void
    {
        $capturedSubject = null;
        $send = static function (string $subject) use (&$capturedSubject): void {
            $capturedSubject = $subject;
        };

        $channel = new MailChannel(true, ['a@example.com'], '', $send);
        $channel->notify($this->makeEvent());

        $this->assertStringContainsString('RuntimeShield Alert', (string) $capturedSubject);
    }

    public function test_notify_passes_recipients_and_from(): void
    {
        $capturedRecipients = null;
        $capturedFrom = null;
        $send = static function (string $subject, string $body, array $recipients, string $from) use (
            &$capturedRecipients,
            &$capturedFrom,
        ): void {
            $capturedRecipients = $recipients;
            $capturedFrom = $from;
        };

        $channel = new MailChannel(true, ['admin@example.com'], 'shield@app.com', $send);
        $channel->notify($this->makeEvent());

        $this->assertSame(['admin@example.com'], $capturedRecipients);
        $this->assertSame('shield@app.com', $capturedFrom);
    }

    public function test_notify_body_contains_violation_title(): void
    {
        $capturedBody = null;
        $send = static function (string $subject, string $body) use (&$capturedBody): void {
            $capturedBody = $body;
        };

        $channel = new MailChannel(true, ['a@example.com'], '', $send);
        $channel->notify($this->makeEvent());

        $this->assertStringContainsString('Missing Rate Limit', (string) $capturedBody);
    }

    public function test_notify_silent_when_recipients_empty(): void
    {
        $called = false;
        $send = static function () use (&$called): void {
            $called = true;
        };

        $channel = new MailChannel(true, [], '', $send);
        $channel->notify($this->makeEvent());

        $this->assertFalse($called);
    }

    public function test_send_exception_is_swallowed(): void
    {
        $send = static function (): void {
            throw new \RuntimeException('SMTP error');
        };

        $channel = new MailChannel(true, ['a@example.com'], '', $send);
        $channel->notify($this->makeEvent()); // must not throw
        $this->addToAssertionCount(1);
    }

    private function makeEvent(): AlertEvent
    {
        return new AlertEvent(
            new ViolationCollection([
                new Violation('missing-rate-limit', 'Missing Rate Limit', 'No throttle middleware', Severity::MEDIUM, 'api/users'),
            ]),
            'api/users',
            new \DateTimeImmutable(),
        );
    }
}
