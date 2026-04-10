<?php

declare(strict_types=1);

namespace RuntimeShield\Contracts;

/**
 * Decides whether the current request should be processed by RuntimeShield.
 *
 * Implementations must be stateless so they can be registered as singletons.
 */
interface SamplerContract
{
    /** Returns true if this request should be sampled (processed). */
    public function shouldSample(): bool;

    /** The configured sampling rate as a fraction between 0.0 and 1.0. */
    public function rate(): float;
}
