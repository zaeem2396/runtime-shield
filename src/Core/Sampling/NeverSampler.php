<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Sampling;

use RuntimeShield\Contracts\SamplerContract;

/**
 * Sampler that unconditionally rejects every request.
 * Useful for disabling sampling in tests or temporarily in production.
 */
final class NeverSampler implements SamplerContract
{
    public function shouldSample(): bool
    {
        return false;
    }

    public function rate(): float
    {
        return 0.0;
    }
}
