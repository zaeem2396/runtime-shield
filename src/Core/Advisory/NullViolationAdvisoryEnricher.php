<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Advisory;

use RuntimeShield\Contracts\Advisory\ViolationAdvisoryEnricherContract;
use RuntimeShield\DTO\Advisory\AdvisorySource;
use RuntimeShield\DTO\Rule\ViolationCollection;

/**
 * No-op enricher used when AI is disabled or not configured.
 */
final class NullViolationAdvisoryEnricher implements ViolationAdvisoryEnricherContract
{
    public function enrich(ViolationCollection $violations, AdvisorySource $source): ViolationCollection
    {
        return $violations;
    }
}
