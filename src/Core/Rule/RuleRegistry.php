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

    /**
     * Remove the rule with the given ID from the registry.
     * Returns true when a rule was found and removed, false when the ID
     * was not registered.
     */
    public function unregister(string $id): bool
    {
        foreach ($this->rules as $index => $rule) {
            if ($rule->id() === $id) {
                array_splice($this->rules, $index, 1);

                return true;
            }
        }

        return false;
    }

    /**
     * Replace an existing rule with the same ID.
     * Returns true when the old rule was found and swapped, false when
     * no rule with that ID existed (in which case the new rule is appended).
     */
    public function replace(RuleContract $rule): bool
    {
        foreach ($this->rules as $index => $existing) {
            if ($existing->id() === $rule->id()) {
                $this->rules[$index] = $rule;

                return true;
            }
        }

        $this->rules[] = $rule;

        return false;
    }

    /**
     * Remove all registered rules.
     * Useful for test teardown or building a fresh rule set from scratch.
     */
    public function reset(): void
    {
        $this->rules = [];
    }

    /**
     * Return the IDs of all currently registered rules.
     *
     * @return list<string>
     */
    public function ids(): array
    {
        return array_values(array_map(
            static fn (RuleContract $r): string => $r->id(),
            $this->rules,
        ));
    }
}
