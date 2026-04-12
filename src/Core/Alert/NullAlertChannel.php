<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Alert;

use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\DTO\Alert\AlertEvent;

/**
 * No-op alert channel used when alerting is globally disabled.
 *
 * isEnabled() always returns false so the dispatcher skips notify()
 * entirely, but the channel can still be registered and inspected via
 * AlertDispatcherContract::channels() without side effects.
 */
final class NullAlertChannel implements AlertChannelContract
{
    public function channelName(): string
    {
        return 'null';
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function notify(AlertEvent $event): void
    {
        // intentional no-op
    }
}
