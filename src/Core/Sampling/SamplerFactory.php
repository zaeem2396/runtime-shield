<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Sampling;

use RuntimeShield\Contracts\SamplerContract;

/**
 * Creates the appropriate SamplerContract implementation for a given rate.
 *
 * - rate <= 0.0 → NeverSampler  (zero-overhead path)
 * - rate >= 1.0 → AlwaysSampler (zero-overhead path)
 * - otherwise   → PercentageSampler
 */
final class SamplerFactory
{
    public static function fromRate(float $rate): SamplerContract
    {
        if ($rate <= 0.0) {
            return new NeverSampler();
        }

        if ($rate >= 1.0) {
            return new AlwaysSampler();
        }

        return new PercentageSampler($rate);
    }
}
