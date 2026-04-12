<?php

declare(strict_types=1);

namespace RuntimeShield\Laravel\Events;

use RuntimeShield\DTO\Alert\AlertEvent;

/**
 * Laravel event fired immediately after the AlertDispatcher has delivered
 * an alert to all registered channels.
 *
 * Listeners can hook into this event to integrate with custom monitoring
 * tools, persist violation records, or trigger secondary workflows without
 * coupling directly to the alert channel implementations.
 *
 * Usage example:
 *
 *   Event::listen(ViolationAlertedEvent::class, function (ViolationAlertedEvent $e) {
 *       // $e->alertEvent->violations, $e->alertEvent->route, etc.
 *   });
 */
final class ViolationAlertedEvent
{
    public function __construct(public readonly AlertEvent $alertEvent)
    {
    }
}
