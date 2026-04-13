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
        public readonly ViolationAdvisory|null $advisory = null,
    ) {
    }

    public function withAdvisory(ViolationAdvisory|null $advisory): self
    {
        return new self(
            ruleId: $this->ruleId,
            title: $this->title,
            description: $this->description,
            severity: $this->severity,
            route: $this->route,
            context: $this->context,
            advisory: $advisory,
        );
    }

    /**
     * Serialize to a JSON-compatible array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $out = [
            'rule_id' => $this->ruleId,
            'title' => $this->title,
            'description' => $this->description,
            'severity' => $this->severity->value,
            'route' => $this->route,
            'context' => $this->context,
        ];

        if ($this->advisory !== null) {
            $out['advisory'] = $this->advisory->toArray();
        }

        return $out;
    }
}
