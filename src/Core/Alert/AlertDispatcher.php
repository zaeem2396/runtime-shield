<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Alert;

use RuntimeShield\Contracts\Alert\AlertChannelContract;
use RuntimeShield\Contracts\Alert\AlertDispatcherContract;
use RuntimeShield\DTO\Alert\AlertEvent;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Multichannel alert dispatcher.
 *
 * Filters violations by the configured minimum severity, builds an
 * AlertEvent, then delivers it to every enabled channel in sequence.
 * If no violations meet the threshold the dispatch is a no-op.
 */
final class AlertDispatcher implements AlertDispatcherContract
{
    /** @var list<AlertChannelContract> */
    private array $channels = [];

    public function __construct(private readonly Severity $minSeverity)
    {
    }

    public function dispatch(ViolationCollection $violations, string $route = ''): void
    {
        $filtered = $this->filterBySeverity($violations);

        if ($filtered->isEmpty()) {
            return;
        }

        $event = new AlertEvent($filtered, $route, new \DateTimeImmutable());

        foreach ($this->channels as $channel) {
            if ($channel->isEnabled()) {
                $channel->notify($event);
            }
        }
    }

    public function addChannel(AlertChannelContract $channel): static
    {
        $this->channels[] = $channel;

        return $this;
    }

    /** @return list<AlertChannelContract> */
    public function channels(): array
    {
        return $this->channels;
    }

    /** The minimum severity a violation must meet to trigger an alert. */
    public function minSeverity(): Severity
    {
        return $this->minSeverity;
    }

    /**
     * Return a new collection containing only violations at or above the
     * minimum severity threshold (lower priority number = higher severity).
     */
    private function filterBySeverity(ViolationCollection $violations): ViolationCollection
    {
        $threshold = $this->minSeverity->priority();

        $filtered = array_values(array_filter(
            $violations->all(),
            static fn (Violation $v): bool => $v->severity->priority() <= $threshold,
        ));

        return new ViolationCollection($filtered);
    }
}
