<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Rule;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Optional base class for custom rule implementations.
 *
 * Provides:
 *  - A default severity of LOW (override via severity())
 *  - A helper make() for building Violation records without repeating
 *    the rule ID and title each time
 *
 * Subclasses MUST implement id(), title(), and evaluate().
 *
 * Usage:
 *
 *   final class MyRule extends AbstractRule
 *   {
 *       public function id(): string    { return 'my-rule'; }
 *       public function title(): string { return 'My Custom Rule'; }
 *
 *       public function evaluate(SecurityRuntimeContext $context): array
 *       {
 *           if ($someCondition) {
 *               return [$this->make('Description of the issue', $context->route?->uri ?? '')];
 *           }
 *           return [];
 *       }
 *   }
 */
abstract class AbstractRule implements RuleContract
{
    /**
     * Default severity is LOW.
     * Override this method to change the severity for your rule.
     */
    public function severity(): Severity
    {
        return Severity::LOW;
    }

    /**
     * Evaluate the context and return any violations found.
     *
     * @return list<Violation>
     */
    abstract public function evaluate(SecurityRuntimeContext $context): array;

    /**
     * Build a Violation for this rule without repeating id() and title().
     *
     * @param array<string, mixed> $context Additional metadata about the violation
     */
    protected function make(
        string $description,
        string $route = '',
        array $context = [],
        Severity|null $severity = null,
    ): Violation {
        return new Violation(
            ruleId: $this->id(),
            title: $this->title(),
            description: $description,
            severity: $severity ?? $this->severity(),
            route: $route,
            context: $context,
        );
    }
}
