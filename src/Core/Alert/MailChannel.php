<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Alert;

use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Violation;

/**
 * Alert channel that sends a plain-text email for every alert event.
 *
 * The actual mail transport is injected as a callable so this class has
 * no hard dependency on Illuminate\Mail and remains unit-testable without
 * a running mailer. The ServiceProvider wires it to the Laravel Mailer.
 *
 * @phpstan-type MailSender \Closure(string $subject, string $body, list<string> $recipients, string $from): void
 */
final class MailChannel implements AlertChannelContract
{
    /**
     * @var \Closure(string, string, list<string>, string): void
     */
    private readonly \Closure $send;

    /**
     * @param list<string> $recipients
     * @param \Closure(string, string, list<string>, string): void|null $send
     *                                                                        Callable that performs the actual delivery. Defaults to a no-op
     *                                                                        when null — ServiceProvider injects the real mailer closure.
     */
    public function __construct(
        private readonly bool $enabled,
        private readonly array $recipients,
        private readonly string $from,
        ?\Closure $send = null,
    ) {
        $this->send = $send ?? static function (string $subject, string $body, array $recipients, string $from): void {
            // no-op default; ServiceProvider replaces with Laravel Mailer
        };
    }

    public function channelName(): string
    {
        return 'mail';
    }

    public function isEnabled(): bool
    {
        return $this->enabled && $this->recipients !== [];
    }

    public function notify(AlertEvent $event): void
    {
        if ($this->recipients === []) {
            return;
        }

        $subject = 'RuntimeShield Alert: ' . $event->summary();
        $body = $this->buildBody($event);

        try {
            ($this->send)($subject, $body, $this->recipients, $this->from);
        } catch (\Throwable) {
            // transport errors are intentionally swallowed
        }
    }

    private function buildBody(AlertEvent $event): string
    {
        $lines = [
            'RuntimeShield Security Alert',
            str_repeat('=', 40),
            '',
            $event->summary(),
            'Route:        ' . ($event->route ?: 'unknown'),
            'Triggered at: ' . $event->triggeredAt->format('Y-m-d H:i:s T'),
            '',
            'Violations:',
            str_repeat('-', 40),
        ];

        foreach ($event->violations->sorted() as $violation) {
            $lines[] = $this->formatViolation($violation);
        }

        return implode("\n", $lines);
    }

    private function formatViolation(Violation $violation): string
    {
        $route = $violation->route !== '' ? ' [' . $violation->route . ']' : '';

        return sprintf(
            '[%s] %s%s — %s',
            $violation->severity->label(),
            $violation->title,
            $route,
            $violation->description,
        );
    }
}
