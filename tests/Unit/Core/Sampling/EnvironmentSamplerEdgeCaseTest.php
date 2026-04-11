<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Sampling;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Sampling\AlwaysSampler;
use RuntimeShield\Core\Sampling\EnvironmentSampler;
use RuntimeShield\Core\Sampling\NeverSampler;
use RuntimeShield\Core\Sampling\PercentageSampler;

final class EnvironmentSamplerEdgeCaseTest extends TestCase
{
    public function test_env_rate_1_always_samples(): void
    {
        $sampler = new EnvironmentSampler(
            rates: ['local' => 1.0],
            currentEnv: 'local',
            fallback: new NeverSampler(),
        );

        $this->assertTrue($sampler->shouldSample());
        $this->assertSame(1.0, $sampler->rate());
    }

    public function test_env_rate_0_never_samples(): void
    {
        $sampler = new EnvironmentSampler(
            rates: ['ci' => 0.0],
            currentEnv: 'ci',
            fallback: new AlwaysSampler(),
        );

        $this->assertFalse($sampler->shouldSample());
        $this->assertSame(0.0, $sampler->rate());
    }

    public function test_env_rate_0_5_uses_percentage_sampler(): void
    {
        $sampler = new EnvironmentSampler(
            rates: ['production' => 0.5],
            currentEnv: 'production',
            fallback: new AlwaysSampler(),
        );

        $this->assertSame(0.5, $sampler->rate());
    }

    public function test_empty_rates_always_falls_back(): void
    {
        $sampler = new EnvironmentSampler(
            rates: [],
            currentEnv: 'local',
            fallback: new NeverSampler(),
        );

        $this->assertFalse($sampler->isEnvironmentConfigured());
        $this->assertSame(0.0, $sampler->rate());
    }

    public function test_resolved_environment_unchanged_even_with_fallback(): void
    {
        $sampler = new EnvironmentSampler(
            rates: ['production' => 0.5],
            currentEnv: 'local',
            fallback: new AlwaysSampler(),
        );

        $this->assertSame('local', $sampler->resolvedEnvironment());
        $this->assertFalse($sampler->isEnvironmentConfigured());
    }
}
