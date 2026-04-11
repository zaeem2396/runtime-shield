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

    /**
     * Build a SamplerContract from the full runtime_shield config array.
     *
     * When `sampling.env_rates` is populated, wraps an EnvironmentSampler
     * around the base sampler. The base rate is derived from `sampling_rate`.
     *
     * @param array<string, mixed> $config Full runtime_shield config array
     * @param string               $env    Current application environment (APP_ENV)
     */
    public static function fromConfig(array $config, string $env = 'production'): SamplerContract
    {
        $baseRate = isset($config['sampling_rate']) && is_numeric($config['sampling_rate'])
            ? (float) $config['sampling_rate']
            : 1.0;

        $baseSampler = self::fromRate($baseRate);

        /** @var array<string, float> $envRates */
        $envRates = [];

        if (isset($config['sampling']['env_rates']) && is_array($config['sampling']['env_rates'])) {
            foreach ($config['sampling']['env_rates'] as $envName => $rate) {
                if (is_string($envName) && is_numeric($rate)) {
                    $envRates[$envName] = (float) $rate;
                }
            }
        }

        if ($envRates === []) {
            return $baseSampler;
        }

        return new EnvironmentSampler($envRates, $env, $baseSampler);
    }
}
