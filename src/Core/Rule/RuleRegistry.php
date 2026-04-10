<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Rule;

use RuntimeShield\Contracts\Rule\RuleContract;

/**
 * Mutable registry that holds the set of active security rules.
 *
 * Bound as a singleton in the container so rules can be pushed at boot time
 * and remain available for the lifetime of the request.
 */
final class RuleRegistry
{
    /** @var list<RuleContract> */
    private array $rules = [];

    public function register(RuleContract $rule): void
    {
        $this->rules[] = $rule;
    }

    /** @return list<RuleContract> */
    public function all(): array
    {
        return $this->rules;
    }

    public function count(): int
    {
        return count($this->rules);
    }

    public function has(string $id): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->id() === $id) {
                return true;
            }
        }

        return false;
    }

    public function find(string $id): RuleContract|null
    {
        foreach ($this->rules as $rule) {
            if ($rule->id() === $id) {
                return $rule;
            }
        }

        return null;
    }
}
