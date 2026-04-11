<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Alert;

use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\Contracts\Alert\AlertDispatcherContract;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Decorator that wraps another AlertDispatcherContract and silences
 * alerts for rules whose cooldown window has not yet expired.
 *
 * Behaviour:
 *  - Each violation's ruleId is checked against the AlertThrottle.
 *  - Only unthrottled violations are forwarded to the inner dispatcher.
 *  - If every violation is throttled the inner dispatch is skipped entirely.
 *  - After forwarding, every dispatched ruleId is recorded in the throttle.
 */
final class ThrottledAlertDispatcher implements AlertDispatcherContract
{
    public function __construct(
        private readonly AlertDispatcherContract $inner,
        private readonly AlertThrottle $throttle,
    ) {
    }

    public function dispatch(ViolationCollection $violations, string $route = ''): void
    {
        $unthrottled = array_values(array_filter(
            $violations->all(),
            fn (Violation $v): bool => ! $this->throttle->isThrottled($v->ruleId),
        ));

        if ($unthrottled === []) {
            return;
        }

        foreach ($unthrottled as $violation) {
            $this->throttle->record($violation->ruleId);
        }

        $this->inner->dispatch(new ViolationCollection($unthrottled), $route);
    }

    public function addChannel(AlertChannelContract $channel): static
    {
        $this->inner->addChannel($channel);

        return $this;
    }

    /** @return list<AlertChannelContract> */
    public function channels(): array
    {
        return $this->inner->channels();
    }

    /** Expose the underlying throttle for inspection or testing. */
    public function throttle(): AlertThrottle
    {
        return $this->throttle;
    }
}
