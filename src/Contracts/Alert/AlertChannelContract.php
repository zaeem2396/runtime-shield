<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Alert;

use RuntimeShield\DTO\Alert\AlertEvent;

/**
 * Contract for a single alert notification channel.
 *
 * Implementations are responsible for delivering violation alerts
 * through a specific transport (log, webhook, mail, Slack, etc.).
 * Each channel decides internally how to format and transmit the event.
 */
interface AlertChannelContract
{
    /** Unique identifier for this channel (e.g. "log", "webhook"). */
    public function channelName(): string;

    /** Whether this channel is currently active. */
    public function isEnabled(): bool;

    /**
     * Deliver the alert event through this channel.
     * Implementations MUST be non-throwing; swallow transport errors internally.
     */
    public function notify(AlertEvent $event): void;
}
