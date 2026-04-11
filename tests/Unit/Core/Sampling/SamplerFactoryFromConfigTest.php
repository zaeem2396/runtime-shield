<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Sampling;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Sampling\AlwaysSampler;
use RuntimeShield\Core\Sampling\EnvironmentSampler;
use RuntimeShield\Core\Sampling\NeverSampler;
use RuntimeShield\Core\Sampling\PercentageSampler;
use RuntimeShield\Core\Sampling\SamplerFactory;

final class SamplerFactoryFromConfigTest extends TestCase
{
    public function test_no_env_rates_returns_always_sampler_when_rate_1(): void
    {
        $sampler = SamplerFactory::fromConfig(['sampling_rate' => 1.0], 'production');
        $this->assertInstanceOf(AlwaysSampler::class, $sampler);
    }

    public function test_no_env_rates_returns_never_sampler_when_rate_0(): void
    {
        $sampler = SamplerFactory::fromConfig(['sampling_rate' => 0.0], 'production');
        $this->assertInstanceOf(NeverSampler::class, $sampler);
    }

    public function test_no_env_rates_returns_percentage_sampler_for_partial_rate(): void
    {
        $sampler = SamplerFactory::fromConfig(['sampling_rate' => 0.5], 'production');
        $this->assertInstanceOf(PercentageSampler::class, $sampler);
    }

    public function test_env_rates_returns_environment_sampler(): void
    {
        $config = [
            'sampling_rate' => 1.0,
            'sampling'      => ['env_rates' => ['production' => 0.5, 'local' => 1.0]],
        ];

        $sampler = SamplerFactory::fromConfig($config, 'production');
        $this->assertInstanceOf(EnvironmentSampler::class, $sampler);
    }

    public function test_env_rates_resolves_correct_rate_for_env(): void
    {
        $config = [
            'sampling_rate' => 1.0,
            'sampling'      => ['env_rates' => ['production' => 0.0, 'local' => 1.0]],
        ];

        $sampler = SamplerFactory::fromConfig($config, 'production');
        $this->assertSame(0.0, $sampler->rate());
    }

    public function test_env_rates_missing_env_falls_back_to_global_rate(): void
    {
        $config = [
            'sampling_rate' => 1.0,
            'sampling'      => ['env_rates' => ['local' => 1.0]],
        ];

        $sampler = SamplerFactory::fromConfig($config, 'staging');
        // 'staging' not listed → falls back to AlwaysSampler (rate=1.0)
        $this->assertSame(1.0, $sampler->rate());
    }

    public function test_missing_sampling_rate_defaults_to_1(): void
    {
        $sampler = SamplerFactory::fromConfig([], 'local');
        $this->assertSame(1.0, $sampler->rate());
    }
}
