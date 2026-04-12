<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Rule;

use RuntimeShield\Contracts\Rule\RuleContract;
use RuntimeShield\Contracts\Rule\RuleRegistrarContract;

/**
 * Fluent builder that wraps RuleRegistry and exposes a convenient API
 * for registering, replacing, and disabling security rules.
 *
 * Bound in the container so application service providers can resolve it
 * and configure rules at boot time.
 */
final class RuleRegistrar implements RuleRegistrarContract
{
    public function __construct(private readonly RuleRegistry $registry) {}

    public function rule(RuleContract $rule): static
    {
        $this->registry->register($rule);

        return $this;
    }

    /**
     * @param list<RuleContract> $rules
     */
    public function rules(array $rules): static
    {
        foreach ($rules as $rule) {
            $this->registry->register($rule);
        }

        return $this;
    }

    public function disable(string $id): static
    {
        $this->registry->unregister($id);

        return $this;
    }

    public function replace(RuleContract $rule): static
    {
        $this->registry->replace($rule);

        return $this;
    }

    /**
     * Expose the underlying registry for read-only inspection.
     */
    public function registry(): RuleRegistry
    {
        return $this->registry;
    }
}
