<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Alert;

use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Contract for the alert dispatcher — the fan-out hub that routes a
 * ViolationCollection to every registered channel.
 *
 * The dispatcher is responsible for:
 *  - Filtering violations by the configured minimum severity
 *  - Building an AlertEvent from the filtered collection
 *  - Iterating enabled channels and calling notify()
 */
interface AlertDispatcherContract
{
    /**
     * Dispatch an alert for the given violations.
     *
     * @param string $route The route name or URI that produced the violations.
     */
    public function dispatch(ViolationCollection $violations, string $route = ''): void;

    /** Register a channel with this dispatcher. */
    public function addChannel(AlertChannelContract $channel): static;

    /**
     * All channels registered with this dispatcher (enabled and disabled).
     *
     * @return list<AlertChannelContract>
     */
    public function channels(): array;
}
