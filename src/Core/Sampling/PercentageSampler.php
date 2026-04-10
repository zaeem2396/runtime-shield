<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Sampling;

use RuntimeShield\Contracts\SamplerContract;

/**
 * Probabilistic sampler that accepts a configurable percentage of requests.
 *
 * - rate  0.0 → never sample
 * - rate  1.0 → always sample
 * - rate  0.x → accept ~x% of requests using mt_rand()
 */
final class PercentageSampler implements SamplerContract
{
    public function __construct(
        private readonly float $samplingRate,
    ) {
    }

    public function shouldSample(): bool
    {
        if ($this->samplingRate <= 0.0) {
            return false;
        }

        if ($this->samplingRate >= 1.0) {
            return true;
        }

        return (mt_rand() / getrandmax()) <= $this->samplingRate;
    }

    public function rate(): float
    {
        return $this->samplingRate;
    }
}
