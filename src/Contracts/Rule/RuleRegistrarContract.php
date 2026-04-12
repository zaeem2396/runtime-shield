<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Rule;

/**
 * Fluent API for registering, replacing, and removing security rules.
 *
 * Intended for use in service providers or boot callbacks where multiple
 * rules need to be configured in a readable chain:
 *
 *   $registrar->rules([new MyRule(), new AnotherRule()])
 *             ->disable('public-route-without-auth')
 *             ->replace(new StrictAuthRule());
 */
interface RuleRegistrarContract
{
    /** Register a single custom rule. */
    public function rule(RuleContract $rule): static;

    /**
     * Register multiple rules at once.
     *
     * @param list<RuleContract> $rules
     */
    public function rules(array $rules): static;

    /**
     * Disable (remove) a built-in rule by its ID.
     * Silently no-ops when the rule is not registered.
     */
    public function disable(string $id): static;

    /**
     * Replace an existing rule with a new implementation sharing the same ID.
     * Appends the rule when the ID does not exist yet.
     */
    public function replace(RuleContract $rule): static;
}
