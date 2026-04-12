<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Alert;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Alert\WebhookChannel;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

final class WebhookChannelTest extends TestCase
{
    public function test_channel_name_is_webhook(): void
    {
        $channel = new WebhookChannel(true, 'https://example.com', 'POST', []);
        $this->assertSame('webhook', $channel->channelName());
    }

    public function test_is_enabled_true_when_url_set(): void
    {
        $channel = new WebhookChannel(true, 'https://example.com', 'POST', []);
        $this->assertTrue($channel->isEnabled());
    }

    public function test_is_enabled_false_when_url_empty(): void
    {
        $channel = new WebhookChannel(true, '', 'POST', []);
        $this->assertFalse($channel->isEnabled());
    }

    public function test_is_enabled_false_when_disabled_flag(): void
    {
        $channel = new WebhookChannel(false, 'https://example.com', 'POST', []);
        $this->assertFalse($channel->isEnabled());
    }

    public function test_notify_calls_sender_with_correct_url(): void
    {
        $capturedUrl = null;
        $sender = static function (string $url) use (&$capturedUrl): bool {
            $capturedUrl = $url;

            return true;
        };

        $channel = new WebhookChannel(true, 'https://hooks.example.com/alert', 'POST', [], $sender);
        $channel->notify($this->makeEvent());

        $this->assertSame('https://hooks.example.com/alert', $capturedUrl);
    }

    public function test_notify_calls_sender_with_json_payload(): void
    {
        $capturedPayload = null;
        $sender = static function (string $url, string $method, string $payload) use (&$capturedPayload): bool {
            $capturedPayload = $payload;

            return true;
        };

        $channel = new WebhookChannel(true, 'https://example.com', 'POST', [], $sender);
        $channel->notify($this->makeEvent());

        $this->assertIsString($capturedPayload);
        $decoded = json_decode((string) $capturedPayload, true);
        $this->assertIsArray($decoded);
        $this->assertArrayHasKey('violations', $decoded);
    }

    public function test_notify_includes_content_type_header(): void
    {
        $capturedHeaders = null;
        $sender = static function (string $url, string $method, string $payload, array $headers) use (&$capturedHeaders): bool {
            $capturedHeaders = $headers;

            return true;
        };

        $channel = new WebhookChannel(true, 'https://example.com', 'POST', [], $sender);
        $channel->notify($this->makeEvent());

        $this->assertArrayHasKey('Content-Type', (array) $capturedHeaders);
        $this->assertSame('application/json', ((array) $capturedHeaders)['Content-Type']);
    }

    public function test_notify_is_silent_when_url_is_empty(): void
    {
        $called = false;
        $sender = static function () use (&$called): bool {
            $called = true;

            return true;
        };

        $channel = new WebhookChannel(true, '', 'POST', [], $sender);
        $channel->notify($this->makeEvent());

        $this->assertFalse($called);
    }

    public function test_sender_exception_is_swallowed(): void
    {
        $sender = static function (): bool {
            throw new \RuntimeException('network error');
        };

        $channel = new WebhookChannel(true, 'https://example.com', 'POST', [], $sender);
        $channel->notify($this->makeEvent()); // must not throw
        $this->addToAssertionCount(1);
    }

    private function makeEvent(): AlertEvent
    {
        return new AlertEvent(
            new ViolationCollection([new Violation('r1', 'Title', 'Desc', Severity::HIGH, 'home')]),
            'home',
            new \DateTimeImmutable(),
        );
    }
}
