<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Sampling;

use RuntimeShield\Contracts\SamplerContract;

/**
 * Chains multiple SamplerContracts with AND logic.
 *
 * A request is sampled only when ALL samplers in the chain agree to sample.
 * The effective rate is the product of all individual rates.
 *
 * Useful for combining a global rate with an environment-specific override,
 * e.g. "only sample 50% of requests AND only in production".
 */
final class SamplerChain implements SamplerContract
{
    /** @var list<SamplerContract> */
    private readonly array $samplers;

    /**
     * @param list<SamplerContract> $samplers At least one sampler is required.
     */
    public function __construct(array $samplers)
    {
        $this->samplers = $samplers;
    }

    public function shouldSample(): bool
    {
        foreach ($this->samplers as $sampler) {
            if (! $sampler->shouldSample()) {
                return false;
            }
        }

        return true;
    }

    /**
     * Effective rate is the product of all individual rates.
     */
    public function rate(): float
    {
        return array_reduce(
            $this->samplers,
            static fn (float $carry, SamplerContract $s): float => $carry * $s->rate(),
            1.0,
        );
    }

    /** Number of samplers in the chain. */
    public function count(): int
    {
        return count($this->samplers);
    }

    /** @return list<SamplerContract> */
    public function samplers(): array
    {
        return $this->samplers;
    }

    /** Whether the chain contains no samplers (always samples when empty). */
    public function isEmpty(): bool
    {
        return $this->samplers === [];
    }
}
