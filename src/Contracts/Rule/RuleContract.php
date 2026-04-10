<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Rule;

use RuntimeShield\DTO\Rule\Severity;
use RuntimeShield\DTO\Rule\Violation;
use RuntimeShield\DTO\SecurityRuntimeContext;

/**
 * Contract for a single security rule.
 *
 * Each rule inspects a SecurityRuntimeContext and returns zero or more
 * Violation records. Rules must be stateless so they can be singletons.
 */
interface RuleContract
{
    /** Unique machine-readable identifier, e.g. "public-route-without-auth". */
    public function id(): string;

    /** Short human-readable name shown in CLI output. */
    public function title(): string;

    /** Default severity when the rule fires. */
    public function severity(): Severity;

    /**
     * Evaluate the context and return any violations found.
     *
     * @return list<Violation>
     */
    public function evaluate(SecurityRuntimeContext $context): array;
}
