<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts\Advisory;

use RuntimeShield\DTO\Advisory\AdvisorySource;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * Augments violations with optional AI advisory metadata.
 */
interface ViolationAdvisoryEnricherContract
{
    /**
     * Returns a new collection with the same deterministic violations; advisory
     * fields may be populated when AI is enabled and the call succeeds.
     */
    public function enrich(ViolationCollection $violations, AdvisorySource $source): ViolationCollection;
}
