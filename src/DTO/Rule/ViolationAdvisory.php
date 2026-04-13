<?php

declare(strict_types=1);

namespace RuntimeShield\DTO\Rule;

/**
 * Optional AI-generated advisory metadata attached to a {@see Violation}.
 *
 * Deterministic rule severity on the parent violation is never modified; this
 * DTO holds a separate advisory severity hint for triage only.
 */
final class ViolationAdvisory
{
    public function __construct(
        public readonly string $summary,
        public readonly string $impact,
        public readonly string $remediation,
        public readonly Severity|null $advisorySeverity = null,
        public readonly float|null $confidence = null,
        public readonly string $rationale = '',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'summary' => $this->summary,
            'impact' => $this->impact,
            'remediation' => $this->remediation,
            'severity' => $this->advisorySeverity?->value,
            'confidence' => $this->confidence,
            'rationale' => $this->rationale,
        ];
    }
}
