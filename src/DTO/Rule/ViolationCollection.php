<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Rule;

/**
 * Typed, immutable collection of Violation records.
 * Produced by the RuleEngine after evaluating a SecurityRuntimeContext.
 */
final class ViolationCollection
{
    /** @var list<Violation> */
    private array $violations;

    /** @param list<Violation> $violations */
    public function __construct(array $violations = [])
    {
        $this->violations = $violations;
    }

    /** @return list<Violation> */
    public function all(): array
    {
        return $this->violations;
    }

    public function count(): int
    {
        return count($this->violations);
    }

    public function isEmpty(): bool
    {
        return $this->violations === [];
    }

    /** @return list<Violation> */
    public function bySeverity(Severity $severity): array
    {
        return array_values(
            array_filter($this->violations, static fn (Violation $v): bool => $v->severity === $severity),
        );
    }

    /** @return list<Violation> */
    public function critical(): array
    {
        return $this->bySeverity(Severity::CRITICAL);
    }

    /** @return list<Violation> */
    public function high(): array
    {
        return $this->bySeverity(Severity::HIGH);
    }

    /** @return list<Violation> */
    public function medium(): array
    {
        return $this->bySeverity(Severity::MEDIUM);
    }

    /** @return list<Violation> */
    public function low(): array
    {
        return $this->bySeverity(Severity::LOW);
    }

    /** Merge another collection into this one, returning a new instance. */
    public function merge(self $other): self
    {
        return new self(array_merge($this->violations, $other->violations));
    }

    /**
     * Return all violations sorted by severity priority (CRITICAL first).
     *
     * @return list<Violation>
     */
    public function sorted(): array
    {
        $copy = $this->violations;
        usort($copy, static fn (Violation $a, Violation $b): int => $a->severity->priority() <=> $b->severity->priority());

        return $copy;
    }
}
