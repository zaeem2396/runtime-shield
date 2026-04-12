<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Alert;

use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Immutable snapshot of an alert lifecycle event.
 *
 * Carries the filtered violation collection, the triggering route, and
 * the timestamp at which the alert was produced. Passed to every
 * AlertChannelContract::notify() call so channels can format it however
 * they need without re-querying the engine.
 */
final class AlertEvent
{
    public function __construct(
        public readonly ViolationCollection $violations,
        public readonly string $route,
        public readonly \DateTimeImmutable $triggeredAt,
    ) {
    }

    /**
     * Short human-readable description of the alert, suitable for
     * log messages and notification subjects.
     */
    public function summary(): string
    {
        $count = $this->violations->count();
        $noun = $count === 1 ? 'violation' : 'violations';
        $route = $this->route !== '' ? $this->route : 'unknown';

        return sprintf('%d %s detected on route [%s]', $count, $noun, $route);
    }

    /**
     * Highest-severity violation in the collection, or null when empty.
     */
    public function highestSeverityViolation(): Violation|null
    {
        $sorted = $this->violations->sorted();

        return $sorted[0] ?? null;
    }

    /**
     * JSON-serialisable representation of the alert event.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary(),
            'route' => $this->route,
            'triggered_at' => $this->triggeredAt->format(\DateTimeInterface::ATOM),
            'violation_count' => $this->violations->count(),
            'violations' => array_map(
                static fn (Violation $v): array => $v->toArray(),
                $this->violations->all(),
            ),
        ];
    }
}
