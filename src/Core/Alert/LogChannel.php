<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Alert;

use Psr\Log\LoggerInterface;
use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Violation;

/**
 * Alert channel that writes a structured log entry for every violation
 * in the alert event using a PSR-3 logger.
 *
 * Log level is derived from the highest severity in the event:
 *   CRITICAL → error, HIGH → warning, MEDIUM → notice, LOW / INFO → info
 */
final class LogChannel implements AlertChannelContract
{
    public function __construct(
        private readonly bool $enabled,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function channelName(): string
    {
        return 'log';
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function notify(AlertEvent $event): void
    {
        $level = $this->resolveLogLevel($event);

        $this->logger->log($level, '[RuntimeShield] ' . $event->summary(), [
            'route' => $event->route,
            'triggered_at' => $event->triggeredAt->format(\DateTimeInterface::ATOM),
            'violations' => array_map(
                static fn (Violation $v): array => $v->toArray(),
                $event->violations->all(),
            ),
        ]);
    }

    /**
     * Map the highest violation severity to a PSR-3 log level string.
     */
    private function resolveLogLevel(AlertEvent $event): string
    {
        $top = $event->highestSeverityViolation();

        if ($top === null) {
            return 'info';
        }

        return match ($top->severity->value) {
            'critical' => 'error',
            'high' => 'warning',
            'medium' => 'notice',
            default => 'info',
        };
    }
}
