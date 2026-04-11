<?php

declare(strict_types=1);

namespace RuntimeShield\Core\Sampling;

use RuntimeShield\Contracts\SamplerContract;

/**
 * Sampling rate that varies by application environment.
 *
 * Allows teams to sample 100% of requests in local/staging while
 * reducing overhead in production, without changing code or .env files
 * on every deploy.
 *
 * When the current environment is not explicitly configured, the
 * behaviour falls back to the provided $fallback sampler (typically
 * built from the global sampling_rate config value).
 */
final class EnvironmentSampler implements SamplerContract
{
    private readonly SamplerContract $resolved;

    /**
     * @param array<string, float> $rates Map of env name → sampling rate (0.0–1.0)
     * @param string $currentEnv Current value of APP_ENV
     * @param SamplerContract $fallback Sampler to use when $currentEnv is not in $rates
     */
    public function __construct(
        private readonly array $rates,
        private readonly string $currentEnv,
        SamplerContract $fallback,
    ) {
        $rate = $rates[$currentEnv] ?? null;
        $this->resolved = $rate !== null ? SamplerFactory::fromRate($rate) : $fallback;
    }

    public function shouldSample(): bool
    {
        return $this->resolved->shouldSample();
    }

    public function rate(): float
    {
        return $this->resolved->rate();
    }

    /** The environment key that was used to resolve the active sampler. */
    public function resolvedEnvironment(): string
    {
        return $this->currentEnv;
    }

    /** Whether the current environment was explicitly configured. */
    public function isEnvironmentConfigured(): bool
    {
        return array_key_exists($this->currentEnv, $this->rates);
    }
}
