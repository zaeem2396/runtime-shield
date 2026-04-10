<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Rule;

/**
 * Immutable record of a single security violation detected by a rule.
 */
final class Violation
{
    /**
     * @param array<string, mixed> $context Additional metadata about the violation
     */
    public function __construct(
        public readonly string $ruleId,
        public readonly string $title,
        public readonly string $description,
        public readonly Severity $severity,
        public readonly string $route = '',
        public readonly array $context = [],
    ) {
    }

    /**
     * Serialize to a JSON-compatible array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'rule_id' => $this->ruleId,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity->value,
            'route' => $this->route,
            'context' => $this->context,
        ];
    }
}
