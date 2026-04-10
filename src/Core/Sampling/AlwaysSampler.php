<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Sampling;

use RuntimeShield\Contracts\SamplerContract;

/**
 * Sampler that unconditionally accepts every request.
 * Intended for use in tests and development environments.
 */
final class AlwaysSampler implements SamplerContract
{
    public function shouldSample(): bool
    {
        return true;
    }

    public function rate(): float
    {
        return 1.0;
    }
}
