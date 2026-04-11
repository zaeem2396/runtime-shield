<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Sampling;

use PHPUnit\Framework\TestCase;
use RuntimeShield\Core\Sampling\AlwaysSampler;
use RuntimeShield\Core\Sampling\EnvironmentSampler;
use RuntimeShield\Core\Sampling\NeverSampler;

final class EnvironmentSamplerTest extends TestCase
{
    public function test_local_env_always_samples(): void
    {
        $sampler = $this->make('local', 1.0);
        $this->assertSame(1.0, $sampler->rate());
    }

    public function test_testing_env_never_samples(): void
    {
        $sampler = $this->make('testing', 1.0);
        $this->assertSame(0.0, $sampler->rate());
        $this->assertFalse($sampler->shouldSample());
    }

    public function test_production_env_uses_configured_rate(): void
    {
        $sampler = $this->make('production', 1.0);
        $this->assertSame(0.5, $sampler->rate());
    }

    public function test_unknown_env_falls_back_to_fallback_sampler(): void
    {
        $sampler = new EnvironmentSampler(
            rates: ['production' => 0.5],
            currentEnv: 'staging',
            fallback: new AlwaysSampler(),
        );

        $this->assertSame(1.0, $sampler->rate());
        $this->assertFalse($sampler->isEnvironmentConfigured());
    }

    public function test_resolved_environment_returns_current_env(): void
    {
        $sampler = $this->make('local', 1.0);
        $this->assertSame('local', $sampler->resolvedEnvironment());
    }

    public function test_is_environment_configured_true_for_known_env(): void
    {
        $sampler = $this->make('production', 1.0);
        $this->assertTrue($sampler->isEnvironmentConfigured());
    }

    public function test_is_environment_configured_false_for_unknown_env(): void
    {
        $sampler = new EnvironmentSampler(
            rates: ['local' => 1.0],
            currentEnv: 'staging',
            fallback: new AlwaysSampler(),
        );

        $this->assertFalse($sampler->isEnvironmentConfigured());
    }

    public function test_should_sample_false_when_rate_is_zero(): void
    {
        $sampler = $this->make('testing', 1.0);
        $this->assertFalse($sampler->shouldSample());
    }

    public function test_should_sample_true_when_rate_is_one(): void
    {
        $sampler = $this->make('local', 1.0);
        $this->assertTrue($sampler->shouldSample());
    }
    private function make(string $env, float $rate): EnvironmentSampler
    {
        return new EnvironmentSampler(
            rates: ['production' => 0.5, 'local' => 1.0, 'testing' => 0.0],
            currentEnv: $env,
            fallback: $rate >= 1.0 ? new AlwaysSampler() : new NeverSampler(),
        );
    }
}
