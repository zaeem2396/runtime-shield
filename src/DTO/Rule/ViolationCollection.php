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
}
