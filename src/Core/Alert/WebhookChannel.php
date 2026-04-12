<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Alert;

use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\DTO\Alert\AlertEvent;

/**
 * Alert channel that POSTs a JSON payload to a configured HTTP endpoint.
 *
 * The actual HTTP transport is injected as a callable, making this class
 * fully unit-testable without an HTTP server. The default implementation
 * uses PHP stream contexts (no curl required).
 *
 * Payload is the JSON-encoded AlertEvent::toArray() output, with a
 * Content-Type: application/json header added automatically.
 *
 * Transport errors are silently swallowed to respect the channel contract
 * that notify() MUST be non-throwing.
 */
final class WebhookChannel implements AlertChannelContract
{
    /**
     * @var \Closure(string $url, string $method, string $payload, array<string, string> $headers): bool
     */
    private readonly \Closure $sender;

    /**
     * @param array<string, string> $headers Additional HTTP headers to include in every request.
     * @param \Closure(string, string, string, array<string, string>): bool|null $sender
     *                                                                                   Custom HTTP sender; defaults to a stream_context implementation.
     */
    public function __construct(
        private readonly bool $enabled,
        private readonly string $url,
        private readonly string $method,
        private readonly array $headers,
        ?\Closure $sender = null,
    ) {
        $this->sender = $sender ?? self::defaultSender();
    }

    public function channelName(): string
    {
        return 'webhook';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->url !== '';
    }

    public function notify(AlertEvent $event): void
    {
        if ($this->url === '') {
            return;
        }

        $payload = (string) json_encode($event->toArray(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $headers = array_merge(
            ['Content-Type' => 'application/json', 'User-Agent' => 'RuntimeShield/0.8'],
            $this->headers,
        );

        try {
            ($this->sender)($this->url, $this->method, $payload, $headers);
        } catch (\Throwable) {
            // transport errors are intentionally swallowed
        }
    }

    /** @return \Closure(string, string, string, array<string, string>): bool */
    private static function defaultSender(): \Closure
    {
        return static function (string $url, string $method, string $payload, array $headers): bool {
            $headerLines = array_map(
                static fn (string $k, string $v): string => "{$k}: {$v}",
                array_keys($headers),
                array_values($headers),
            );

            $context = stream_context_create([
                'http' => [
                    'method' => strtoupper($method),
                    'header' => implode("\r\n", $headerLines),
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
