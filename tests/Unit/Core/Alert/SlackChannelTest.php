<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Alert;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Alert\SlackChannel;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class SlackChannelTest extends TestCase
{
    public function test_channel_name_is_slack(): void
    {
        $this->assertSame('slack', (new SlackChannel(true, 'https://hooks.slack.com/x'))->channelName());
    }

    public function test_is_enabled_true_when_url_set(): void
    {
        $this->assertTrue((new SlackChannel(true, 'https://hooks.slack.com/x'))->isEnabled());
    }

    public function test_is_enabled_false_when_url_empty(): void
    {
        $this->assertFalse((new SlackChannel(true, ''))->isEnabled());
    }

    public function test_is_enabled_false_when_disabled_flag(): void
    {
        $this->assertFalse((new SlackChannel(false, 'https://hooks.slack.com/x'))->isEnabled());
    }

    public function test_notify_sends_to_webhook_url(): void
    {
        $capturedUrl = null;
        $sender = static function (string $url) use (&$capturedUrl): bool {
            $capturedUrl = $url;

            return true;
        };

        $channel = new SlackChannel(true, 'https://hooks.slack.com/T/B/x', $sender);
        $channel->notify($this->makeEvent());

        $this->assertSame('https://hooks.slack.com/T/B/x', $capturedUrl);
    }

    public function test_notify_sends_json_with_text_key(): void
    {
        $capturedPayload = null;
        $sender = static function (string $url, string $payload) use (&$capturedPayload): bool {
            $capturedPayload = $payload;

            return true;
        };

        $channel = new SlackChannel(true, 'https://hooks.slack.com/x', $sender);
        $channel->notify($this->makeEvent());

        $decoded = json_decode((string) $capturedPayload, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('text', $decoded);
        $this->assertStringContainsString('RuntimeShield Alert', (string) $decoded['text']);
    }

    public function test_notify_text_contains_severity_label(): void
    {
        $capturedText = null;
        $sender = static function (string $url, string $payload) use (&$capturedText): bool {
            $decoded = json_decode($payload, true);
            $capturedText = is_array($decoded) ? ($decoded['text'] ?? '') : '';

            return true;
        };

        $channel = new SlackChannel(true, 'https://hooks.slack.com/x', $sender);
        $channel->notify($this->makeEvent(Severity::CRITICAL));

        $this->assertStringContainsString('CRITICAL', (string) $capturedText);
    }

    public function test_notify_silent_when_url_empty(): void
    {
        $called = false;
        $sender = static function () use (&$called): bool {
            $called = true;

            return true;
        };

        $channel = new SlackChannel(true, '', $sender);
        $channel->notify($this->makeEvent());
        $this->assertFalse($called);
    }

    public function test_sender_exception_is_swallowed(): void
    {
        $sender = static function (): bool {
            throw new \RuntimeException('timeout');
        };

        $channel = new SlackChannel(true, 'https://hooks.slack.com/x', $sender);
        $channel->notify($this->makeEvent()); // must not throw
        $this->addToAssertionCount(1);
    }

    private function makeEvent(Severity $severity = Severity::HIGH): AlertEvent
    {
        return new AlertEvent(
            new ViolationCollection([new Violation('r1', 'Title', 'Desc', $severity, 'dashboard')]),
            'dashboard',
            new \DateTimeImmutable(),
        );
    }
}
