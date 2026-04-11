<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Alert;

use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Violation;

/**
 * Alert channel that delivers a formatted message to a Slack Incoming Webhook URL.
 *
 * The message lists every violation with its severity label and route,
 * prefixed by the overall alert summary. An injectable sender closure
 * keeps this class fully unit-testable without a running Slack workspace.
 *
 * Transport errors are silently swallowed per the channel contract.
 */
final class SlackChannel implements AlertChannelContract
{
    /**
     * @var \Closure(string $url, string $payload): bool
     */
    private readonly \Closure $sender;

    /**
     * @param \Closure(string, string): bool|null $sender Custom HTTP sender; defaults to stream_context.
     */
    public function __construct(
        private readonly bool $enabled,
        private readonly string $webhookUrl,
        ?\Closure $sender = null,
    ) {
        $this->sender = $sender ?? self::defaultSender();
    }

    public function channelName(): string
    {
        return 'slack';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->webhookUrl !== '';
    }

    public function notify(AlertEvent $event): void
    {
        if ($this->webhookUrl === '') {
            return;
        }

        $payload = (string) json_encode(
            ['text' => $this->buildSlackText($event)],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        try {
            ($this->sender)($this->webhookUrl, $payload);
        } catch (\Throwable) {
            // transport errors are intentionally swallowed
        }
    }

    private function buildSlackText(AlertEvent $event): string
    {
        $lines = ['*RuntimeShield Alert* — ' . $event->summary()];

        foreach ($event->violations->sorted() as $violation) {
            $lines[] = $this->formatViolationLine($violation);
        }

        return implode("\n", $lines);
    }

    private function formatViolationLine(Violation $violation): string
    {
        $label = '[' . $violation->severity->label() . ']';
        $route = $violation->route !== '' ? ' (`' . $violation->route . '`)' : '';

        return '• ' . $label . ' ' . $violation->title . $route;
    }

    /** @return \Closure(string, string): bool */
    private static function defaultSender(): \Closure
    {
        return static function (string $url, string $payload): bool {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nUser-Agent: RuntimeShield/0.8",
                    'content' => $payload,
                    'ignore_errors' => true,
                    'timeout' => 5,
                ],
            ]);

            $result = @file_get_contents($url, false, $context);

            return $result !== false;
        };
    }
}
